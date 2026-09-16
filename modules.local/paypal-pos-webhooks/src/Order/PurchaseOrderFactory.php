<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WP_Error;

/**
 * Creates anonymous WooCommerce orders from Zettle POS purchases.
 *
 * Amounts arrive in minor units and are scaled here; the order never reduces stock
 * (handled by the InventoryBalanceChanged path) and sends no emails.
 */
class PurchaseOrderFactory
{
    private const KEY_VARIANT_UUID = 'variantUuid';
    private const KEY_PRODUCT_UUID = 'productUuid';
    private const KEY_QUANTITY = 'quantity';
    private const KEY_UNIT_PRICE = 'unitPrice';
    private const KEY_NAME = 'name';

    private const SUPPRESSED_EMAILS = [
        'new_order',
        'customer_completed_order',
        'customer_processing_order',
        'customer_on_hold_order',
        'customer_invoice',
    ];

    private OneToManyMapInterface $variantIdMap;

    private PaymentLabels $paymentLabels;

    private LoggerInterface $logger;

    public function __construct(
        OneToManyMapInterface $variantIdMap,
        PaymentLabels $paymentLabels,
        LoggerInterface $logger
    ) {

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
            'meta_query' => [
                [
                    'key' => OrderMeta::PURCHASE_UUID,
                    'value' => $purchaseUuid,
                ],
            ],
        ]);

        return !empty($ids);
    }

    public function createOrder(Purchase $purchase): int
    {
        $this->suppressEmails(true);
        // Stock is owned by the InventoryBalanceChanged path; block reduction on save.
        add_filter('woocommerce_can_reduce_order_stock', '__return_false');

        try {
            $order = wc_create_order();
            if ($order instanceof WP_Error) {
                throw new RuntimeException($order->get_error_message());
            }
            $currency = $purchase->currency();

            foreach ($purchase->products() as $line) {
                $this->addLineItem($order, (array) $line, $currency);
            }

            $order->set_currency($currency);
            $order->set_date_created($purchase->timestamp()->getTimestamp());
            $order->set_created_via('zettle_pos');
            $this->applyPayment($order, $purchase->payments());
            $this->applyMeta($order, $purchase);

            // Mirror Zettle's own totals; never let WC recompute and diverge.
            $order->set_cart_tax((string) MinorUnits::toMajor($purchase->vatAmount(), $currency));
            $order->set_total((string) MinorUnits::toMajor($purchase->amount(), $currency));
            $order->set_status('completed');
            $order->save();

            // Record the order as already stock-reduced so later transitions don't reduce it.
            $order->get_data_store()->set_stock_reduced($order->get_id(), true);

            $order->add_order_note(sprintf('Cashier: %s', $purchase->user()->displayName()));

            return $order->get_id();
        } finally {
            $this->suppressEmails(false);
            remove_filter('woocommerce_can_reduce_order_stock', '__return_false');
        }
    }

    private function addLineItem(WC_Order $order, array $line, string $currency): void
    {
        $quantity = (int) ($line[self::KEY_QUANTITY] ?? 1);
        $unitPriceMinor = (int) ($line[self::KEY_UNIT_PRICE] ?? 0);
        $lineTotal = (string) MinorUnits::toMajor($unitPriceMinor * $quantity, $currency);
        $variantUuid = (string) ($line[self::KEY_VARIANT_UUID] ?? '');

        $productId = $this->matchProduct($variantUuid);
        if ($productId === null) {
            $fee = new WC_Order_Item_Fee();
            $fee->set_name((string) ($line[self::KEY_NAME] ?? 'Zettle item'));
            $fee->set_amount($lineTotal);
            $fee->set_total($lineTotal);
            $order->add_item($fee);

            return;
        }

        $item = new WC_Order_Item_Product();
        $item->set_product(wc_get_product($productId));
        $item->set_quantity($quantity);
        $item->set_subtotal($lineTotal);
        $item->set_total($lineTotal);
        $item->add_meta_data(OrderMeta::ITEM_VARIANT_UUID, $variantUuid);
        $item->add_meta_data(OrderMeta::ITEM_PRODUCT_UUID, (string) ($line[self::KEY_PRODUCT_UUID] ?? ''));
        $item->add_meta_data(OrderMeta::ITEM_UNIT_PRICE_MINOR, $unitPriceMinor);
        $order->add_item($item);
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
        $order->update_meta_data(OrderMeta::PAYMENTS, wp_json_encode(array_values($payments)));
    }

    private function applyMeta(WC_Order $order, Purchase $purchase): void
    {
        $order->update_meta_data(OrderMeta::PURCHASE_UUID, $purchase->uuid());
        $order->update_meta_data(OrderMeta::PURCHASE_NUMBER, $purchase->purchaseNumber());
        if ($purchase->sourceType() !== null) {
            $order->update_meta_data(OrderMeta::SOURCE_TYPE, $purchase->sourceType()->getValue());
        }
    }

    private function suppressEmails(bool $suppress): void
    {
        foreach (self::SUPPRESSED_EMAILS as $emailId) {
            $filter = "woocommerce_email_enabled_{$emailId}";
            if ($suppress) {
                add_filter($filter, '__return_false');
                continue;
            }
            remove_filter($filter, '__return_false');
        }
    }
}
