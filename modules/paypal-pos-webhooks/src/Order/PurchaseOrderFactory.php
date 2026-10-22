<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Tax;
use WC_Product;
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
    public const DEFAULT_LINE_NAME = 'PayPal POS item';
    private const FILTER_PRIORITY = 1000;
    private const SUPPRESSED_EMAILS = ['new_order', 'customer_completed_order', 'customer_processing_order', 'customer_on_hold_order', 'customer_invoice'];
    private OneToManyMapInterface $variantIdMap;
    private PaymentLabels $paymentLabels;
    private LoggerInterface $logger;
    public function __construct(OneToManyMapInterface $variantIdMap, PaymentLabels $paymentLabels, LoggerInterface $logger)
    {
        $this->variantIdMap = $variantIdMap;
        $this->paymentLabels = $paymentLabels;
        $this->logger = $logger;
    }
    public function orderExists(string $purchaseUuid): bool
    {
        $ids = wc_get_orders([
            'limit' => 1,
            'return' => 'ids',
            // 'any' excludes trash; an explicit list including 'trash' is required.
            'status' => array_merge(array_keys(wc_get_order_statuses()), ['trash']),
            'meta_query' => [['key' => OrderMeta::PURCHASE_UUID, 'value' => $purchaseUuid]],
        ]);
        return !empty($ids);
    }
    public function createOrder(Purchase $purchase): int
    {
        $this->blockStockReduction(\true);
        try {
            // Built in memory and saved once, so a failure leaves no half-created order behind.
            $order = new WC_Order();
            $currency = $purchase->currency();
            $taxTotals = [];
            $discountMinor = 0;
            $discountTaxMinor = 0;
            foreach ($purchase->products() as $line) {
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
                $this->alignLineTaxes($order, $taxTotals[$key]['rateId'], $vatMinor - $taxTotals[$key]['taxMinor'], $currency);
                $taxTotals[$key]['taxMinor'] = $vatMinor;
            }
            $this->addTaxItems($order, $taxTotals, $currency);
            $taxMinor = (int) array_sum(array_column($taxTotals, 'taxMinor'));
            $order->set_discount_total((string) MinorUnits::toMajor($discountMinor, $currency));
            $order->set_discount_tax((string) MinorUnits::toMajor($discountTaxMinor, $currency));
            $order->set_currency($currency);
            $order->set_prices_include_tax(\false);
            $order->set_date_created($purchase->timestamp()->getTimestamp());
            $order->set_created_via(self::CREATED_VIA);
            // Record the order as already stock-reduced so later transitions don't reduce it.
            $order->set_order_stock_reduced(\true);
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
            $order->add_order_note(sprintf('Cashier: %s', $purchase->user()->displayName()));
            return $order->get_id();
        } finally {
            $this->blockStockReduction(\false);
        }
    }
    /**
     * @param array<string, mixed> $line
     * @param array<int|string, array{rateId: int, percentage: float, taxMinor: int}> $taxTotals
     *
     * @return LineAmounts|null The product line's amounts, or null when it became a fee line.
     */
    private function addLineItem(WC_Order $order, array $line, string $currency, string $country, array &$taxTotals): ?LineAmounts
    {
        $unitPriceMinor = (int) ($line[self::KEY_UNIT_PRICE] ?? 0);
        $variantUuid = (string) ($line[self::KEY_VARIANT_UUID] ?? '');
        $amounts = LineAmounts::fromLine($line);
        $rateId = 0;
        if ($amounts->taxMinor() > 0) {
            $rateId = $this->taxRateId($amounts->vatPercentage(), $country);
            $key = $rateId > 0 ? $rateId : 'p' . (string) $amounts->vatPercentage();
            $taxTotals[$key] = ['rateId' => $rateId, 'percentage' => $amounts->vatPercentage(), 'taxMinor' => ($taxTotals[$key]['taxMinor'] ?? 0) + $amounts->taxMinor()];
        }
        $productId = $this->matchProduct($variantUuid);
        $product = $productId !== null ? wc_get_product($productId) : null;
        if (!$product instanceof WC_Product) {
            $this->addFeeLine($order, $line, $amounts, $rateId, $currency);
            return null;
        }
        $item = new WC_Order_Item_Product();
        $item->set_product($product);
        // WC rounds this via `woocommerce_stock_amount` unless the store allows fractions; keep
        // fractions under 1 visible as 1 and the real quantity in meta.
        $quantity = wc_stock_amount($amounts->quantity()) > 0 ? $amounts->quantity() : 1;
        $item->set_quantity($quantity);
        // @phpstan-ignore argument.type
        $item->add_meta_data(OrderMeta::ITEM_QUANTITY, (string) $amounts->quantity());
        $item->set_subtotal((string) MinorUnits::toMajor($amounts->subtotalNetMinor(), $currency));
        $item->set_total((string) MinorUnits::toMajor($amounts->netMinor(), $currency));
        // Line taxes need a real WC rate id; unmatched rates still appear as order tax items.
        if ($rateId > 0) {
            $item->set_taxes(['subtotal' => [$rateId => (string) MinorUnits::toMajor($amounts->subtotalTaxMinor(), $currency)], 'total' => [$rateId => (string) MinorUnits::toMajor($amounts->taxMinor(), $currency)]]);
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
    private function addFeeLine(WC_Order $order, array $line, LineAmounts $amounts, int $rateId, string $currency): void
    {
        $net = (string) MinorUnits::toMajor($amounts->netMinor(), $currency);
        $fee = new WC_Order_Item_Fee();
        $fee->set_name((string) ($line[self::KEY_NAME] ?? '') ?: self::DEFAULT_LINE_NAME);
        $fee->set_amount($net);
        $fee->set_total($net);
        if ($rateId > 0) {
            $fee->set_taxes(['total' => [$rateId => (string) MinorUnits::toMajor($amounts->taxMinor(), $currency)]]);
        }
        $fee->add_meta_data(OrderMeta::ITEM_VARIANT_UUID, (string) ($line[self::KEY_VARIANT_UUID] ?? ''));
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
            $tax->set_label(sprintf('Tax %s%%', $percentage));
            $tax->set_rate_percent($taxTotal['percentage']);
            $tax->set_compound(\false);
            $tax->set_tax_total((string) MinorUnits::toMajor($taxTotal['taxMinor'], $currency));
            $tax->set_shipping_tax_total('0');
            $order->add_item($tax);
        }
    }
    /**
     * Finds a WC tax rate with the same percentage, preferring the purchase country.
     */
    private function taxRateId(float $percentage, string $country): int
    {
        global $wpdb;
        $rateId = $wpdb->get_var($wpdb->prepare("SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates\n                WHERE tax_rate = %f AND tax_rate_country IN (%s, '')\n                ORDER BY tax_rate_country DESC, tax_rate_order ASC LIMIT 1", $percentage, strtoupper($country)));
        return (int) $rateId;
    }
    private function matchProduct(string $variantUuid): ?int
    {
        if ($variantUuid === '') {
            return null;
        }
        try {
            return $this->variantIdMap->localId($variantUuid);
        } catch (IdNotFoundException $exception) {
            $this->logger->info(sprintf('No WooCommerce product for Zettle variant %s; adding as a fee line.', $variantUuid));
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
     * Keeps WC from sending order emails for POS orders. Registered for the whole request,
     * not just during creation, because stores that defer emails send them later.
     */
    public static function suppressEmails(): void
    {
        foreach (self::SUPPRESSED_EMAILS as $emailId) {
            add_filter("woocommerce_email_enabled_{$emailId}", [self::class, 'emailEnabled'], 10, 2);
        }
    }
    /**
     * @internal Filter callback for suppressEmails().
     *
     * @param mixed $enabled
     * @param mixed $object
     */
    public static function emailEnabled($enabled, $object = null): bool
    {
        return !($object instanceof WC_Order && $object->get_created_via() === self::CREATED_VIA) && (bool) $enabled;
    }
    /**
     * Stock is owned by the InventoryBalanceChanged path. Our own callback, so removing it never
     * touches an identical filter added by others.
     */
    private function blockStockReduction(bool $block): void
    {
        if ($block) {
            add_filter('woocommerce_can_reduce_order_stock', [self::class, 'disable'], self::FILTER_PRIORITY);
            return;
        }
        remove_filter('woocommerce_can_reduce_order_stock', [self::class, 'disable'], self::FILTER_PRIORITY);
    }
    /**
     * @internal Filter callback for blockStockReduction().
     */
    public static function disable(): bool
    {
        return \false;
    }
    /**
     * Moves the cent Zettle's VAT total and the rounded line taxes can differ by onto the last
     * line with that rate, so line taxes add up to the order tax.
     */
    private function alignLineTaxes(WC_Order $order, int $rateId, int $differenceMinor, string $currency): void
    {
        if ($rateId <= 0 || $differenceMinor === 0) {
            return;
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
            $taxes['total'][$rateId] = (string) MinorUnits::toMajor(MinorUnits::toMinor((float) $taxes['total'][$rateId], $currency) + $differenceMinor, $currency);
            $line->set_taxes($taxes);
            return;
        }
    }
}
