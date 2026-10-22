<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

use WC_Order;
/**
 * Keeps POS orders from triggering WooCommerce side effects: order emails and stock
 * reduction. Stock is owned by the InventoryBalanceChanged path.
 */
class OrderSideEffects
{
    private const FILTER_PRIORITY = 1000;
    private const SUPPRESSED_EMAILS = ['new_order', 'customer_completed_order', 'customer_processing_order', 'customer_on_hold_order', 'customer_invoice'];
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
     *
     * @param mixed $enabled
     * @param mixed $object
     */
    public function emailEnabled($enabled, $object = null): bool
    {
        return !($object instanceof WC_Order && $object->get_created_via() === PurchaseOrderFactory::CREATED_VIA) && (bool) $enabled;
    }
    /**
     * Runs $create with stock reduction blocked.
     *
     * @template T
     * @param callable(): T $create
     * @return T
     */
    public function withoutStockReduction(callable $create): mixed
    {
        // Our own callback, so removing it never touches an identical filter added by others.
        add_filter('woocommerce_can_reduce_order_stock', [$this, 'disable'], self::FILTER_PRIORITY);
        try {
            return $create();
        } finally {
            remove_filter('woocommerce_can_reduce_order_stock', [$this, 'disable'], self::FILTER_PRIORITY);
        }
    }
    /**
     * @internal Filter callback for withoutStockReduction().
     */
    public function disable(): bool
    {
        return \false;
    }
}
