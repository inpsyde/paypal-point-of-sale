<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\GiftCardPayment;

interface GiftcardPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return GiftCardPayment
     */
    public function buildFromArray(array $data): GiftCardPayment;

    /**
     * @param GiftCardPayment $giftCardPayment
     *
     * @return array
     */
    public function createDataArray(GiftCardPayment $giftCardPayment): array;
}
