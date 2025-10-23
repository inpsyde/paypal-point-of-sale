<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;

final class MobilePayment extends AbstractPaymentMethod
{
    /**
     * MobilePayment constructor.
     *
     * @param string $uuid
     * @param float $amount
     */
    public function __construct(string $uuid, float $amount)
    {
        parent::__construct($uuid, $amount, PaymentType::mobilePayment());
    }
}
