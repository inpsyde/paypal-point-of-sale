<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\CashPayment;

interface CashPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return CashPayment
     */
    public function buildFromArray(array $data): CashPayment;

    /**
     * @param CashPayment $cashPayment
     *
     * @return array
     */
    public function createDataArray(CashPayment $cashPayment): array;
}
