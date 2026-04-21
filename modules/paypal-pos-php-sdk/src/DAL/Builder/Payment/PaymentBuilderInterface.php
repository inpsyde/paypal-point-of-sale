<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\BuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
interface PaymentBuilderInterface extends BuilderInterface
{
    /**
     * @param array $data
     *
     * @return AbstractPaymentMethod
     */
    public function buildFromArray(array $data): AbstractPaymentMethod;
    /**
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return array
     */
    public function createDataArray(AbstractPaymentMethod $paymentMethod): array;
}
