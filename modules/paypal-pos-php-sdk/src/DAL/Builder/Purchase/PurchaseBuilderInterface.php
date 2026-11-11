<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Purchase;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
interface PurchaseBuilderInterface
{
    /**
     * @param array $data
     *
     * @return Purchase
     */
    public function buildFromArray(array $data): Purchase;
    /**
     * @param Purchase $purchase
     * @return array
     */
    public function createDataArray(Purchase $purchase): array;
}
