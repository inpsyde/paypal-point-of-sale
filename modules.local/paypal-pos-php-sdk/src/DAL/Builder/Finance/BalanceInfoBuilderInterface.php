<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Finance;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\BalanceInfo;

interface BalanceInfoBuilderInterface
{
    /**
     * @param array $data
     *
     * @return BalanceInfo
     */
    public function buildFromArray(array $data): BalanceInfo;

    /**
     * @param BalanceInfo $balanceInfo
     *
     * @return array
     */
    public function createDataArray(BalanceInfo $balanceInfo): array;
}
