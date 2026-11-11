<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Purchase;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseHistory;
interface PurchaseHistoryBuilderInterface
{
    /**
     * @param array $data
     *
     * @return PurchaseHistory
     */
    public function buildFromArray(array $data): PurchaseHistory;
    /**
     * @param PurchaseHistory $purchase
     * @return array
     */
    public function createDataArray(PurchaseHistory $purchase): array;
}
