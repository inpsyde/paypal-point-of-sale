<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use WC_Product;
// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
class ImageConnectionFilter implements FilterInterface
{
    /**
     * @inheritDoc
     */
    public function accepts($entity, $payload): bool
    {
        return $entity instanceof ImageInterface and $payload instanceof WC_Product;
    }
    /**
     * @inheritDoc
     */
    public function filter($image, $payload)
    {
        return $image;
    }
}
