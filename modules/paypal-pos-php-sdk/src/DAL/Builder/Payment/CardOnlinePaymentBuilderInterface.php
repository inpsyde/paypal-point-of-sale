<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\CardOnlinePayment;
interface CardOnlinePaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return CardOnlinePayment
     */
    public function buildFromArray(array $data): CardOnlinePayment;
    /**
     * @param CardOnlinePayment $cardOnlinePayment
     *
     * @return array
     */
    public function createDataArray(CardOnlinePayment $cardOnlinePayment): array;
}
