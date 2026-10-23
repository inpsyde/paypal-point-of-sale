<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

/**
 * Keys of a line in a Zettle purchase's `products` array.
 */
final class PurchaseLine
{
    public const VARIANT_UUID = 'variantUuid';
    public const PRODUCT_UUID = 'productUuid';
    public const UNIT_PRICE = 'unitPrice';
    public const NAME = 'name';
}
