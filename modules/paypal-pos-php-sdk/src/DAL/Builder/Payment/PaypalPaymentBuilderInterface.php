<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaypalPayment;
interface PaypalPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return PaypalPayment
     */
    public function buildFromArray(array $data): PaypalPayment;
    /**
     * @param PaypalPayment $paypalPayment
     *
     * @return array
     */
    public function createDataArray(PaypalPayment $paypalPayment): array;
}
