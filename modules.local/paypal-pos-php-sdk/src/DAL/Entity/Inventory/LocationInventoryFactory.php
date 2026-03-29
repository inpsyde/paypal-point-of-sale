<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Inventory;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance\LocationBalanceCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductCollection;

class LocationInventoryFactory
{
    public function create(
        string $uuid,
        ProductCollection $trackedProducts,
        LocationBalanceCollection $locationBalances
    ): LocationInventory {

        return new LocationInventory(
            $uuid,
            $trackedProducts,
            $locationBalances
        );
    }
}
