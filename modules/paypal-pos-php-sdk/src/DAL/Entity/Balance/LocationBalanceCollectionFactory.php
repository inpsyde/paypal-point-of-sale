<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance;

class LocationBalanceCollectionFactory
{
    /**
     * @return LocationBalanceCollection
     */
    public function create(): LocationBalanceCollection
    {
        return new LocationBalanceCollection();
    }
}
