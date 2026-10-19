<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\PriceAwareInterface;
/**
 * Sets Price to null.
 */
class PriceFilter implements FilterInterface
{
    public function accepts(mixed $entity, mixed $payload): bool
    {
        return $entity instanceof PriceAwareInterface;
    }
    public function filter(mixed $variant, mixed $wcProduct): PriceAwareInterface
    {
        assert($variant instanceof PriceAwareInterface);
        $variant->setPrice(null);
        return $variant;
    }
}
