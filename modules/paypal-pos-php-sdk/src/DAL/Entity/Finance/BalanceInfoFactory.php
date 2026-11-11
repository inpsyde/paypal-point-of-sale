<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance;

class BalanceInfoFactory
{
    /**
     * @param string $totalBalance
     * @param string $currencyId
     *
     * @return BalanceInfo
     */
    public function create(string $totalBalance, string $currencyId): BalanceInfo
    {
        return new BalanceInfo((float) $totalBalance, $currencyId);
    }
}
