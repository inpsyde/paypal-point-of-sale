<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use WC_Product;
class ImageConnectionFilter implements FilterInterface
{
    public function accepts(mixed $entity, mixed $payload): bool
    {
        return $entity instanceof ImageInterface and $payload instanceof WC_Product;
    }
    public function filter(mixed $image, mixed $payload): mixed
    {
        return $image;
    }
}
