<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

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

    public const ITEM_VARIANT_UUID = '_zettle_variant_uuid';
    public const ITEM_PRODUCT_UUID = '_zettle_product_uuid';
    public const ITEM_UNIT_PRICE_MINOR = '_zettle_unit_price_minor';
}
