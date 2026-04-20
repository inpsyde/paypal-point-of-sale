<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;
final class VippsPayment extends AbstractPaymentMethod
{
    /**
     * VippsPayment constructor.
     *
     * @param string $uuid
     * @param float $amount
     */
    public function __construct(string $uuid, float $amount)
    {
        parent::__construct($uuid, $amount, PaymentType::SwishPayment());
    }
}
