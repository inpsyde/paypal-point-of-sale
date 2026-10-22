<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Purchase;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseCollection;
interface PurchaseCollectionBuilderInterface
{
    /**
     * @param array $data
     *
     * @return PurchaseCollection
     */
    public function buildFromArray(array $data): PurchaseCollection;
    /**
     * @param PurchaseCollection $purchaseCollection
     *
     * @return array
     */
    public function createDataArray(PurchaseCollection $purchaseCollection): array;
}
