<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\CustomPayment;

interface CustomPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return CustomPayment
     */
    public function buildFromArray(array $data): CustomPayment;

    /**
     * @param CustomPayment $customPayment
     *
     * @return array
     */
    public function createDataArray(CustomPayment $customPayment): array;
}
