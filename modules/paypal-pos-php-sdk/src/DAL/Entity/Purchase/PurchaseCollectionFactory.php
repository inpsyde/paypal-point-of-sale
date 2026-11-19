<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase;

final class PurchaseCollectionFactory
{
    /**
     * @return PurchaseCollection
     */
    public function create(): PurchaseCollection
    {
        return new PurchaseCollection();
    }
}
