<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Finance;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\PayoutInfo;
interface PayoutInfoBuilderInterface
{
    /**
     * @param array $data
     *
     * @return PayoutInfo
     */
    public function buildFromArray(array $data): PayoutInfo;
    /**
     * @param PayoutInfo $payoutInfo
     *
     * @return array
     */
    public function createDataArray(PayoutInfo $payoutInfo): array;
}
