<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Finance;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\PayoutInfo;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\PayoutInfoFactory;

class PayoutInfoBuilder implements PayoutInfoBuilderInterface
{
    /**
     * @var PayoutInfoFactory
     */
    private $payoutInfoFactory;

    /**
     * PayoutInfoBuilder constructor.
     * @param PayoutInfoFactory $payoutInfoFactory
     */
    public function __construct(PayoutInfoFactory $payoutInfoFactory)
    {
        $this->payoutInfoFactory = $payoutInfoFactory;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): PayoutInfo
    {
        return $this->build($data);
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(PayoutInfo $payoutInfo): array
    {
        return [
            'totalBalance' => $payoutInfo->totalBalance(),
            'currencyId' => $payoutInfo->currencyId(),
            'nextPayoutAmount' => $payoutInfo->nextPayoutAmount(),
            'discountRemaining' => $payoutInfo->discountRemaining(),
            'period' => $payoutInfo->period()->getValue(),
        ];
    }

    /**
     * @param array $data
     *
     * @return PayoutInfo
     */
    private function build(array $data): PayoutInfo
    {
        return $this->payoutInfoFactory->create(
            $data['totalBalance'],
            $data['currencyId'],
            $data['nextPayoutAmount'],
            $data['discountRemaining'],
            $data['period']
        );
    }
}
