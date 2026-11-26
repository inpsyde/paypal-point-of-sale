<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Inventory;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantInventoryState\VariantInventoryStateCollection;
class InventoryFactory
{
    public function create(VariantInventoryStateCollection $variants): Inventory
    {
        return new Inventory($variants);
    }
}
