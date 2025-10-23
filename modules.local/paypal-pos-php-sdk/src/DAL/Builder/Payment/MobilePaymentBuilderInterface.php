<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\MobilePayment;

interface MobilePaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return MobilePayment
     */
    public function buildFromArray(array $data): MobilePayment;

    /**
     * @param MobilePayment $mobilePayment
     *
     * @return array
     */
    public function createDataArray(MobilePayment $mobilePayment): array;
}
