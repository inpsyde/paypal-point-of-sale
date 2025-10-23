<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\CardPayment;

interface CardPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return CardPayment
     */
    public function buildFromArray(array $data): CardPayment;

    /**
     * @param CardPayment $cardPayment
     *
     * @return array
     */
    public function createDataArray(CardPayment $cardPayment): array;
}
