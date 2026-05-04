<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Inventory;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantChangeHistory\VariantInventoryState;
class InventoryFactory
{
    /**
     * @param VariantInventoryState $variants
     *
     * @return Inventory
     */
    public function create(VariantInventoryState $variants): Inventory
    {
        return new Inventory($variants);
    }
}
