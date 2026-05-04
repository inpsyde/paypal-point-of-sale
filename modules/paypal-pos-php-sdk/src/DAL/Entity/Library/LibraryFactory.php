<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Library;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount\DiscountCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductCollection;
class LibraryFactory
{
    public function create(string $untilEventLogUuid, string $fromEventLogUuid, ProductCollection $products, DiscountCollection $discounts, ?ProductCollection $deletedProducts = null, ?DiscountCollection $deletedDiscounts = null): Library
    {
        return new Library($untilEventLogUuid, $fromEventLogUuid, $products, $discounts, $deletedProducts, $deletedDiscounts);
    }
}
