<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use WC_Order;
/**
 * Order fields shared by every WC order created from a POS purchase: currency, dates, origin
 * and payment method.
 */
class OrderDefaults
{
    private PaymentLabels $paymentLabels;
    public function __construct(PaymentLabels $paymentLabels)
    {
        $this->paymentLabels = $paymentLabels;
    }
    public function apply(WC_Order $order, Purchase $purchase): void
    {
        $order->set_currency($purchase->currency());
        $order->set_prices_include_tax(\false);
        $order->set_date_created($purchase->timestamp()->getTimestamp());
        $order->set_created_via(PurchaseOrderFactory::CREATED_VIA);
        // Record the order as already stock-reduced so later transitions don't reduce it.
        $order->set_order_stock_reduced(\true);
        $payments = $purchase->payments();
        $primaryType = $this->paymentLabels->primaryType($payments);
        $order->set_payment_method($this->paymentLabels->slug($primaryType));
        $order->set_payment_method_title($this->paymentLabels->title($primaryType));
        $order->update_meta_data(OrderMeta::PRIMARY_PAYMENT_TYPE, $primaryType);
        $order->update_meta_data(OrderMeta::PAYMENTS, (string) wp_json_encode(array_values($payments)));
        // WooCommerce Order Attribution, shown as the order's "Origin".
        $order->update_meta_data(OrderMeta::ATTRIBUTION_SOURCE_TYPE, PurchaseOrderFactory::ORIGIN_SOURCE_TYPE);
        $order->update_meta_data(OrderMeta::ATTRIBUTION_UTM_SOURCE, PurchaseOrderFactory::ORIGIN_SOURCE);
    }
}
