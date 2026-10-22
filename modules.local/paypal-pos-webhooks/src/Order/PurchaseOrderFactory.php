<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\MinorUnits;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Tax;
use WC_Product;
use WC_Tax;

/**
 * Creates anonymous WooCommerce orders from Zettle POS purchases.
 */
class PurchaseOrderFactory
{
    private const KEY_VARIANT_UUID = 'variantUuid';
    private const KEY_PRODUCT_UUID = 'productUuid';
    private const KEY_UNIT_PRICE = 'unitPrice';
    private const KEY_NAME = 'name';

    public const CREATED_VIA = 'paypal_pos';

    private OneToManyMapInterface $variantIdMap;

    private PaymentLabels $paymentLabels;

    private OrderSideEffects $sideEffects;

    private LoggerInterface $logger;

    /**
     * @var array<string, int> WC tax rate ids by country and percentage.
     */
    private array $taxRateIds = [];

    public function __construct(
        OneToManyMapInterface $variantIdMap,
        PaymentLabels $paymentLabels,
        OrderSideEffects $sideEffects,
        LoggerInterface $logger
    ) {

        $this->variantIdMap = $variantIdMap;
        $this->paymentLabels = $paymentLabels;
        $this->sideEffects = $sideEffects;
        $this->logger = $logger;
    }

    public function orderExists(string $purchaseUuid): bool
    {
        if ($purchaseUuid === '') {
            return false;
        }

        $ids = wc_get_orders([
            'limit' => 1,
            'return' => 'ids',
            // 'any' excludes trash; an explicit list including 'trash' is required.
            'status' => array_merge(array_keys(wc_get_order_statuses()), ['trash']),
            // meta_key/meta_value, not meta_query: the legacy post order store ignores meta_query.
            'meta_key' => OrderMeta::PURCHASE_UUID,
            'meta_value' => $purchaseUuid,
        ]);

        return !empty($ids);
    }

    public function createOrder(Purchase $purchase): int
    {
        return $this->sideEffects->withoutStockReduction(function () use ($purchase): int {
            // Built in memory and saved once, so a failure leaves no half-created order behind.
            $order = new WC_Order();
            $currency = $purchase->currency();

            $taxTotals = [];
            $discountMinor = 0;
            $discountTaxMinor = 0;
            $linesNetMinor = 0;
            foreach ($purchase->products() as $line) {
                $linesNetMinor += LineAmounts::fromLine((array) $line)->netMinor();
                // Only product lines carry a subtotal, so only they contribute to the order discount.
                $amounts = $this->addLineItem($order, (array) $line, $currency, $purchase->country(), $taxTotals);
                if ($amounts !== null) {
                    $discountMinor += $amounts->subtotalNetMinor() - $amounts->netMinor();
                    $discountTaxMinor += $amounts->subtotalTaxMinor() - $amounts->taxMinor();
                }
            }
            // With a single rate, use Zettle's purchase VAT so per-line rounding can't drift.
            if (count($taxTotals) === 1) {
                $key = array_key_first($taxTotals);
                $vatMinor = (int) abs($purchase->vatAmount());
                // The cent moved onto a line's tax comes off the discount tax, which is subtotal minus total.
                $discountTaxMinor -= $this->alignLineTaxes(
                    $order,
                    $taxTotals[$key]['rateId'],
                    $vatMinor - $taxTotals[$key]['taxMinor'],
                    $currency
                );
                $taxTotals[$key]['taxMinor'] = $vatMinor;
            }
            $this->addTaxItems($order, $taxTotals, $currency);
            $taxMinor = (int) array_sum(array_column($taxTotals, 'taxMinor'));
            $this->addUnlistedCharges($order, $purchase, (int) abs($purchase->amount()) - $linesNetMinor - $taxMinor);
            $order->set_discount_total((string) MinorUnits::toMajor($discountMinor, $currency));
            $order->set_discount_tax((string) MinorUnits::toMajor($discountTaxMinor, $currency));

            $order->set_currency($currency);
            $order->set_prices_include_tax(false);
            $order->set_date_created($purchase->timestamp()->getTimestamp());
            $order->set_created_via(self::CREATED_VIA);
            // Record the order as already stock-reduced so later transitions don't reduce it.
            $order->set_order_stock_reduced(true);
            $this->applyPayment($order, $purchase->payments());
            $this->applyMeta($order, $purchase);

            // Mirror Zettle's own totals; never let WC recompute and diverge.
            $order->set_cart_tax((string) MinorUnits::toMajor($taxMinor, $currency));
            $order->set_total((string) MinorUnits::toMajor($purchase->amount(), $currency));
            $order->set_status('completed');
            // set_status() stamps both dates with the current time; use the sale's time instead.
            $order->set_date_paid($purchase->timestamp()->getTimestamp());
            $order->set_date_completed($purchase->timestamp()->getTimestamp());
            $order->save();

            $order->add_order_note(
                /* translators: %s: name of the cashier who made the sale */
                sprintf(__('Cashier: %s', 'paypal-point-of-sale'), $purchase->user()->displayName())
            );

            return $order->get_id();
        });
    }

    /**
     * @param array<string, mixed> $line
     * @param array<int|string, array{rateId: int, percentage: float, taxMinor: int}> $taxTotals
     *
     * @return LineAmounts|null The product line's amounts, or null when it became a fee line.
     */
    private function addLineItem(
        WC_Order $order,
        array $line,
        string $currency,
        string $country,
        array &$taxTotals
    ): ?LineAmounts {

        $unitPriceMinor = (int) ($line[self::KEY_UNIT_PRICE] ?? 0);
        $variantUuid = (string) ($line[self::KEY_VARIANT_UUID] ?? '');
        $amounts = LineAmounts::fromLine($line);

        $rateId = 0;
        // A fully discounted line has no tax left but still needs its rate for the subtotal tax.
        if ($amounts->subtotalTaxMinor() > 0) {
            $rateId = $this->taxRateId($amounts->vatPercentage(), $country);
        }
        if ($amounts->taxMinor() > 0) {
            $key = $rateId > 0 ? $rateId : 'p' . (string) $amounts->vatPercentage();
            $taxTotals[$key] = [
                'rateId' => $rateId,
                'percentage' => $amounts->vatPercentage(),
                'taxMinor' => ($taxTotals[$key]['taxMinor'] ?? 0) + $amounts->taxMinor(),
            ];
        }

        $productId = $this->matchProduct($variantUuid);
        $product = $productId !== null ? wc_get_product($productId) : null;
        if (!$product instanceof WC_Product) {
            $this->addFeeLine($order, $line, $amounts, $rateId, $currency);

            return null;
        }

        $item = new WC_Order_Item_Product();
        $item->set_product($product);
        // WC rounds quantities to whole numbers by default; the real one is kept in meta.
        $quantity = wc_stock_amount($amounts->quantity()) > 0 ? $amounts->quantity() : 1;
        $item->set_quantity($quantity); // @phpstan-ignore argument.type
        $item->add_meta_data(OrderMeta::ITEM_QUANTITY, (string) $amounts->quantity());
        $item->set_subtotal((string) MinorUnits::toMajor($amounts->subtotalNetMinor(), $currency));
        $item->set_total((string) MinorUnits::toMajor($amounts->netMinor(), $currency));
        // Line taxes need a real WC rate id; unmatched rates still appear as order tax items.
        if ($rateId > 0) {
            $item->set_taxes([
                'subtotal' => [$rateId => (string) MinorUnits::toMajor($amounts->subtotalTaxMinor(), $currency)],
                'total' => [$rateId => (string) MinorUnits::toMajor($amounts->taxMinor(), $currency)],
            ]);
        }
        $item->add_meta_data(OrderMeta::ITEM_VARIANT_UUID, $variantUuid);
        $item->add_meta_data(OrderMeta::ITEM_PRODUCT_UUID, (string) ($line[self::KEY_PRODUCT_UUID] ?? ''));
        $item->add_meta_data(OrderMeta::ITEM_UNIT_PRICE_MINOR, (string) $unitPriceMinor);
        $order->add_item($item);

        return $amounts;
    }

    /**
     * @param array<string, mixed> $line
     */
    private function addFeeLine(
        WC_Order $order,
        array $line,
        LineAmounts $amounts,
        int $rateId,
        string $currency
    ): void {

        $net = (string) MinorUnits::toMajor($amounts->netMinor(), $currency);
        $fee = new WC_Order_Item_Fee();
        $fee->set_name((string) ($line[self::KEY_NAME] ?? '') ?: self::defaultLineName());
        $fee->set_amount($net);
        $fee->set_total($net);
        if ($rateId > 0) {
            $fee->set_taxes(['total' => [$rateId => (string) MinorUnits::toMajor($amounts->taxMinor(), $currency)]]);
        }
        $fee->add_meta_data(OrderMeta::ITEM_VARIANT_UUID, (string) ($line[self::KEY_VARIANT_UUID] ?? ''));
        $order->add_item($fee);
    }

    /**
     * Adds the part of the purchase amount no sold line covers: tips, then anything left, such
     * as a service charge.
     */
    private function addUnlistedCharges(WC_Order $order, Purchase $purchase, int $gapMinor): void
    {
        if ($gapMinor <= 0) {
            return;
        }

        $tipMinor = 0;
        foreach ($purchase->payments() as $payment) {
            $tipMinor += abs((int) (((array) $payment)['gratuityAmount'] ?? 0));
        }
        $tipMinor = min($tipMinor, $gapMinor);

        $this->addChargeLine($order, __('Tip', 'paypal-point-of-sale'), $tipMinor, $purchase->currency());
        $this->addChargeLine(
            $order,
            __('Other charges', 'paypal-point-of-sale'),
            $gapMinor - $tipMinor,
            $purchase->currency()
        );
    }

    private function addChargeLine(WC_Order $order, string $name, int $amountMinor, string $currency): void
    {
        if ($amountMinor <= 0) {
            return;
        }

        $amount = (string) MinorUnits::toMajor($amountMinor, $currency);
        $fee = new WC_Order_Item_Fee();
        $fee->set_name($name);
        $fee->set_tax_status('none');
        $fee->set_amount($amount);
        $fee->set_total($amount);
        $order->add_item($fee);
    }

    /**
     * @param array<int|string, array{rateId: int, percentage: float, taxMinor: int}> $taxTotals
     */
    private function addTaxItems(WC_Order $order, array $taxTotals, string $currency): void
    {
        foreach ($taxTotals as $taxTotal) {
            $percentage = rtrim(rtrim(number_format($taxTotal['percentage'], 2, '.', ''), '0'), '.');
            $tax = new WC_Order_Item_Tax();
            $tax->set_rate_id($taxTotal['rateId']);
            $tax->set_rate_code(sprintf('PAYPAL-POS-TAX-%s', $percentage));
            /* translators: %s: tax rate percentage, e.g. 20 */
            $tax->set_label(sprintf(__('Tax %s%%', 'paypal-point-of-sale'), $percentage));
            $tax->set_rate_percent($taxTotal['percentage']);
            $tax->set_compound(false);
            $tax->set_tax_total((string) MinorUnits::toMajor($taxTotal['taxMinor'], $currency));
            $tax->set_shipping_tax_total('0');
            $order->add_item($tax);
        }
    }

    /**
     * Finds the WC tax rate with the same percentage for the purchase country, in the standard
     * tax class first.
     */
    private function taxRateId(float $percentage, string $country): int
    {
        $cacheKey = strtoupper($country) . '|' . (string) $percentage;
        if (!array_key_exists($cacheKey, $this->taxRateIds)) {
            $this->taxRateIds[$cacheKey] = $this->findTaxRateId($percentage, $country);
        }

        return $this->taxRateIds[$cacheKey];
    }

    private function findTaxRateId(float $percentage, string $country): int
    {
        foreach (array_merge([''], WC_Tax::get_tax_class_slugs()) as $taxClass) {
            $rates = WC_Tax::find_rates(['country' => strtoupper($country), 'tax_class' => $taxClass]);
            foreach ($rates as $rateId => $rate) {
                if (abs((float) $rate['rate'] - $percentage) < 0.0001) {
                    return (int) $rateId;
                }
            }
        }

        return 0;
    }

    private function matchProduct(string $variantUuid): ?int
    {
        if ($variantUuid === '') {
            return null;
        }

        try {
            return $this->variantIdMap->localId($variantUuid);
        } catch (IdNotFoundException $exception) {
            $this->logger->info(
                sprintf('No WooCommerce product for Zettle variant %s; adding as a fee line.', $variantUuid)
            );

            return null;
        }
    }

    /**
     * @param array<int, mixed> $payments
     */
    private function applyPayment(WC_Order $order, array $payments): void
    {
        $primaryType = $this->paymentLabels->primaryType($payments);

        $order->set_payment_method($this->paymentLabels->slug($primaryType));
        $order->set_payment_method_title($this->paymentLabels->title($primaryType));
        $order->update_meta_data(OrderMeta::PRIMARY_PAYMENT_TYPE, $primaryType);
        $order->update_meta_data(OrderMeta::PAYMENTS, (string) wp_json_encode(array_values($payments)));
    }

    private function applyMeta(WC_Order $order, Purchase $purchase): void
    {
        $order->update_meta_data(OrderMeta::PURCHASE_UUID, $purchase->uuid());
        $order->update_meta_data(OrderMeta::PURCHASE_NUMBER, (string) $purchase->purchaseNumber());
        if ($purchase->sourceType() !== null) {
            $order->update_meta_data(OrderMeta::SOURCE_TYPE, $purchase->sourceType()->getValue());
        }
    }

    /**
     * Name for sold items Zettle sends without one, e.g. custom amounts.
     */
    public static function defaultLineName(): string
    {
        return __('PayPal POS item', 'paypal-point-of-sale');
    }

    /**
     * Moves the cent Zettle's VAT total and the rounded line taxes can differ by onto the last
     * line with that rate, so line taxes add up to the order tax.
     *
     * @return int The difference applied, in minor units.
     */
    private function alignLineTaxes(WC_Order $order, int $rateId, int $differenceMinor, string $currency): int
    {
        if ($rateId <= 0 || $differenceMinor === 0) {
            return 0;
        }

        $lines = array_reverse((array) $order->get_items(['line_item', 'fee']));
        foreach ($lines as $line) {
            if (!$line instanceof WC_Order_Item_Product && !$line instanceof WC_Order_Item_Fee) {
                continue;
            }
            $taxes = (array) $line->get_taxes();
            if (!isset($taxes['total'][$rateId])) {
                continue;
            }
            $taxes['total'][$rateId] = (string) MinorUnits::toMajor(
                MinorUnits::toMinor((float) $taxes['total'][$rateId], $currency) + $differenceMinor,
                $currency
            );
            $line->set_taxes($taxes);

            return $differenceMinor;
        }

        return 0;
    }
}
