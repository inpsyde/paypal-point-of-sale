<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\PriceAwareInterface;
/**
 * Sets Price to null.
 */
class PriceFilter implements FilterInterface
{
    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function accepts($entity, $payload): bool
    {
        // phpcs:enable
        return $entity instanceof PriceAwareInterface;
    }
    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     */
    public function filter($variant, $wcProduct)
    {
        // phpcs:enable
        assert($variant instanceof PriceAwareInterface);
        $variant->setPrice(null);
        return $variant;
    }
}
