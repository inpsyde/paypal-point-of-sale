<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentCollection;
interface PaymentCollectionBuilderInterface
{
    /**
     * @param PaymentCollection $paymentCollection
     *
     * @return array
     */
    public function createDataArray(PaymentCollection $paymentCollection): array;
    /**
     * @param array $data
     *
     * @return PaymentCollection
     */
    public function buildFromArray(array $data): PaymentCollection;
}
