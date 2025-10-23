<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Balance;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance\Balance;

interface BalanceBuilderInterface
{
    /**
     * @param array $data
     *
     * @return Balance
     */
    public function buildFromArray(array $data): Balance;

    /**
     * @param Balance $balance
     *
     * @return array
     */
    public function createDataArray(Balance $balance): array;
}
