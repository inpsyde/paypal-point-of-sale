<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\Type\PeriodType;

final class PayoutInfo
{
    /**
     * @var float
     */
    private $totalBalance;

    /**
     * @var string
     */
    private $currencyId;

    /**
     * @var float
     */
    private $nextPayoutAmount;

    /**
     * @var float
     */
    private $discountRemaining;

    /**
     * @var PeriodType
     */
    private $period;

    /**
     * PayoutInfo constructor.
     * @param float $totalBalance
     * @param string $currencyId
     * @param float $nextPayoutAmount
     * @param float $discountRemaining
     * @param PeriodType $period
     */
    public function __construct(
        float $totalBalance,
        string $currencyId,
        float $nextPayoutAmount,
        float $discountRemaining,
        PeriodType $period
    ) {

        $this->totalBalance = $totalBalance;
        $this->currencyId = $currencyId;
        $this->nextPayoutAmount = $nextPayoutAmount;
        $this->discountRemaining = $discountRemaining;
        $this->period = $period;
    }

    public function totalBalance(): float
    {
        return $this->totalBalance;
    }

    /**
     * @return string
     */
    public function currencyId(): string
    {
        return $this->currencyId;
    }

    public function nextPayoutAmount(): float
    {
        return $this->nextPayoutAmount;
    }

    public function discountRemaining(): float
    {
        return $this->discountRemaining;
    }

    public function period(): PeriodType
    {
        return $this->period;
    }
}
