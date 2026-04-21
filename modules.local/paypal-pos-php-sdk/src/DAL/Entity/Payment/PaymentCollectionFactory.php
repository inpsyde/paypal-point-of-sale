<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

final class PaymentCollectionFactory
{
    /**
     * @return PaymentCollection
     */
    public function create(): PaymentCollection
    {
        return new PaymentCollection();
    }
}
