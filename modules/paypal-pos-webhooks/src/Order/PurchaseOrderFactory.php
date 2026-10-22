<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\MinorUnits;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
/**
 * Creates anonymous WooCommerce orders from Zettle POS purchases.
 */
class PurchaseOrderFactory
{
    public const CREATED_VIA = 'paypal_pos';
    public const ORIGIN_SOURCE_TYPE = 'utm';
    public const ORIGIN_SOURCE = 'PayPal POS';
    private OneToManyMapInterface $variantIdMap;
    private OrderDefaults $defaults;
    private OrderTaxes $taxes;
    private OrderSideEffects $sideEffects;
    private OrderRepository $orders;
    private LoggerInterface $logger;
    public function __construct(OneToManyMapInterface $variantIdMap, OrderDefaults $defaults, OrderTaxes $taxes, OrderSideEffects $sideEffects, OrderRepository $orders, LoggerInterface $logger)
    {
        $this->variantIdMap = $variantIdMap;
        $this->defaults = $defaults;
        $this->taxes = $taxes;
        $this->sideEffects = $sideEffects;
        $this->orders = $orders;
        $this->logger = $logger;
    }
    public function orderExists(string $purchaseUuid): bool
    {
        return $this->orders->existsForPurchaseUuid($purchaseUuid);
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
            $linesMinor = 0;
            foreach ($purchase->products() as $line) {
                $amounts = LineAmounts::fromLine((array) $line);
                $linesMinor += $amounts->netMinor() + $amounts->taxMinor();
                $rateId = $this->taxes->addLineTax($taxTotals, $amounts, $purchase->country());
                // Only product lines carry a subtotal, so only they contribute to the order discount.
                if ($this->addLineItem($order, (array) $line, $amounts, $rateId, $currency)) {
                    $discountMinor += $amounts->subtotalNetMinor() - $amounts->netMinor();
                    $discountTaxMinor += $amounts->subtotalTaxMinor() - $amounts->taxMinor();
                }
            }
            // The cent moved onto a line's tax comes off the discount tax, which is subtotal minus total.
            $vatMinor = (int) abs($purchase->vatAmount());
            $discountTaxMinor -= $this->taxes->alignToPurchaseVat($order, $taxTotals, $vatMinor, $currency);
            $taxMinor = $this->taxes->addTaxItems($order, $taxTotals, $currency);
            // Against the lines as sent, so the rounding cent moved onto a line's tax isn't a charge.
            OrderFees::addUnlistedCharges($order, $purchase, (int) abs($purchase->amount()) - $linesMinor);
            $order->set_discount_total((string) MinorUnits::toMajor($discountMinor, $currency));
            $order->set_discount_tax((string) MinorUnits::toMajor($discountTaxMinor, $currency));
            $this->defaults->apply($order, $purchase);
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
     * Name for sold items Zettle sends without one, e.g. custom amounts.
     */
    public static function defaultLineName(): string
    {
        return __('PayPal POS item', 'paypal-point-of-sale');
    }
    /**
     * @param array<string, mixed> $line
     *
     * @return bool Whether the line became a product item rather than a fee line.
     */
    private function addLineItem(WC_Order $order, array $line, LineAmounts $amounts, int $rateId, string $currency): bool
    {
        $variantUuid = (string) ($line[PurchaseLine::VARIANT_UUID] ?? '');
        $productId = $this->matchProduct($variantUuid);
        $product = $productId !== null ? wc_get_product($productId) : null;
        if (!$product instanceof WC_Product) {
            $this->addFeeLine($order, $line, $amounts, $rateId, $currency);
            return \false;
        }
        $item = new WC_Order_Item_Product();
        $item->set_product($product);
        // WC rounds quantities to whole numbers by default; the real one is kept in meta.
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
        $item->add_meta_data(OrderMeta::ITEM_PRODUCT_UUID, (string) ($line[PurchaseLine::PRODUCT_UUID] ?? ''));
        $item->add_meta_data(OrderMeta::ITEM_UNIT_PRICE_MINOR, (string) (int) ($line[PurchaseLine::UNIT_PRICE] ?? 0));
        $order->add_item($item);
        return \true;
    }
    /**
     * @param array<string, mixed> $line
     */
    private function addFeeLine(WC_Order $order, array $line, LineAmounts $amounts, int $rateId, string $currency): void
    {
        $fee = OrderFees::create((string) ($line[PurchaseLine::NAME] ?? '') ?: self::defaultLineName(), $amounts->netMinor(), $currency);
        if ($rateId > 0) {
            $fee->set_taxes(['total' => [$rateId => (string) MinorUnits::toMajor($amounts->taxMinor(), $currency)]]);
        }
        $fee->add_meta_data(OrderMeta::ITEM_VARIANT_UUID, (string) ($line[PurchaseLine::VARIANT_UUID] ?? ''));
        $order->add_item($fee);
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
    private function applyMeta(WC_Order $order, Purchase $purchase): void
    {
        $order->update_meta_data(OrderMeta::PURCHASE_UUID, $purchase->uuid());
        $order->update_meta_data(OrderMeta::PURCHASE_NUMBER, (string) $purchase->purchaseNumber());
        if ($purchase->sourceType() !== null) {
            $order->update_meta_data(OrderMeta::SOURCE_TYPE, $purchase->sourceType()->getValue());
        }
    }
}
