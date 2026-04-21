<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Balance;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance\LocationBalanceCollection;
interface LocationBalanceCollectionBuilderInterface
{
    /**
     * @param array $data
     *
     * @return LocationBalanceCollection
     */
    public function buildFromArray(array $data): LocationBalanceCollection;
    /**
     * @param LocationBalanceCollection $locationBalanceCollection
     *
     * @return array
     */
    public function createDataArray(LocationBalanceCollection $locationBalanceCollection): array;
}
