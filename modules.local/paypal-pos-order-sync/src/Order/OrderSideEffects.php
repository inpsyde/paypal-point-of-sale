<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\OrderSync\Order;

use WC_Order;
use WC_Order_Item;

/**
 * Keeps POS orders from triggering WooCommerce side effects: order emails and stock
 * reduction. Stock is owned by the InventoryBalanceChanged path.
 */
class OrderSideEffects
{
    private const FILTER_PRIORITY = 1000;

    private const SUPPRESSED_EMAILS = [
        'new_order',
        'customer_completed_order',
        'customer_processing_order',
        'customer_on_hold_order',
        'customer_invoice',
        'customer_refunded_order',
        // WC switches the refund email to this id for partial refunds.
        'customer_partially_refunded_order',
    ];

    /**
     * Registered for the whole request, not just during creation, because stores that defer
     * emails send them later.
     */
    public function suppressEmails(): void
    {
        foreach (self::SUPPRESSED_EMAILS as $emailId) {
            add_filter("woocommerce_email_enabled_{$emailId}", [$this, 'emailEnabled'], 10, 2);
        }
    }

    /**
     * @internal Filter callback for suppressEmails().
     */
    public function emailEnabled(mixed $enabled, mixed $object = null): bool
    {
        return !$this->isPosOrder($object) && (bool) $enabled;
    }

    /**
     * Registered for the whole request, not just during creation: saving a POS order's items or
     * moving it back to a paid status would otherwise reduce stock again. Lines get no
     * _reduced_stock meta either, so cancelling or restocking refunds doesn't add stock back.
     */
    public function preventStockChanges(): void
    {
        add_filter('woocommerce_can_reduce_order_stock', [$this, 'canReduceOrderStock'], self::FILTER_PRIORITY, 2);
        add_filter(
            'woocommerce_prevent_adjust_line_item_product_stock',
            [$this, 'preventLineItemStockAdjustment'],
            self::FILTER_PRIORITY,
            2
        );
    }

    /**
     * @internal Filter callback for preventStockChanges().
     */
    public function canReduceOrderStock(mixed $canReduce, mixed $order = null): bool
    {
        return !$this->isPosOrder($order) && (bool) $canReduce;
    }

    /**
     * @internal Filter callback for preventStockChanges().
     */
    public function preventLineItemStockAdjustment(mixed $prevent, mixed $item = null): bool
    {
        return (bool) $prevent || ($item instanceof WC_Order_Item && $this->isPosOrder($item->get_order()));
    }

    private function isPosOrder(mixed $order): bool
    {
        return $order instanceof WC_Order && $order->get_created_via() === PurchaseOrderFactory::CREATED_VIA;
    }
}
