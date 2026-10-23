<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

/**
 * Meta keys written to POS orders and their line items.
 */
final class OrderMeta
{
    public const PURCHASE_UUID = '_zettle_purchase_uuid';
    public const PURCHASE_NUMBER = '_zettle_purchase_number';
    public const SOURCE_TYPE = '_zettle_source_type';
    public const PRIMARY_PAYMENT_TYPE = '_zettle_primary_payment_type';
    public const PAYMENTS = '_zettle_payments';
    public const ATTRIBUTION_SOURCE_TYPE = '_wc_order_attribution_source_type';
    public const ATTRIBUTION_UTM_SOURCE = '_wc_order_attribution_utm_source';
    public const ITEM_VARIANT_UUID = '_zettle_variant_uuid';
    public const ITEM_PRODUCT_UUID = '_zettle_product_uuid';
    public const ITEM_UNIT_PRICE_MINOR = '_zettle_unit_price_minor';
    public const ITEM_QUANTITY = '_zettle_quantity';
    public const REFUND_PURCHASE_UUID = '_zettle_refund_purchase_uuid';
    public const REFUND_PAYMENTS = '_zettle_refund_payments';
    public const UNAPPLIED_REFUND_UUID = '_zettle_unapplied_refund_uuid';
    public const ORPHAN_REFUND = '_zettle_orphan_refund';
    public const ORPHAN_REFUNDS_PURCHASE_UUID = '_zettle_orphan_refunds_purchase_uuid';
}
