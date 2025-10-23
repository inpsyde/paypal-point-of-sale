<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\KlarnaPayment;

interface KlarnaPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return KlarnaPayment
     */
    public function buildFromArray(array $data): KlarnaPayment;

    /**
     * @param KlarnaPayment $klarnaPayment
     *
     * @return array
     */
    public function createDataArray(KlarnaPayment $klarnaPayment): array;
}
