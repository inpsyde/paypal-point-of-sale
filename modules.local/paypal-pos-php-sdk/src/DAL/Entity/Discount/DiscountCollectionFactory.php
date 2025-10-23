<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount;

class DiscountCollectionFactory
{
    /**
     * @return DiscountCollection
     */
    public function create(): DiscountCollection
    {
        return new DiscountCollection();
    }
}
