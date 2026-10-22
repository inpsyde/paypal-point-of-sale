<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase;

class PurchaseHistoryFactory
{
    /**
     * @param string $firstPurchaseHash
     * @param string $lastPurchaseHash
     * @param PurchaseCollection $purchaseCollection
     *
     * @return PurchaseHistory
     */
    public function create(string $firstPurchaseHash, string $lastPurchaseHash, PurchaseCollection $purchaseCollection): PurchaseHistory
    {
        return new PurchaseHistory($firstPurchaseHash, $lastPurchaseHash, $purchaseCollection);
    }
}
