<?php

# -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

final class PaymentCollection
{
    /**
     * @var AbstractPaymentMethod[]
     */
    private $collection = [];

    /**
     * AbstractPaymentMethodCollection constructor.
     *
     * @param array|null $payments
     */
    public function __construct(?array $payments = [])
    {
        foreach ($payments as $payment) {
            if ($payment instanceof AbstractPaymentMethod) {
                $this->add($payment);
            }
        }
    }

    /**
     * @param AbstractPaymentMethod $payment
     *
     * @return PaymentCollection
     */
    public function add(AbstractPaymentMethod $payment): self
    {
        $this->collection[(string) $payment->uuid()] = $payment;

        return $this;
    }

    /**
     * @param AbstractPaymentMethod $payment
     *
     * @return PaymentCollection
     */
    public function remove(AbstractPaymentMethod $payment): self
    {
        unset($this->collection[(string) $payment->uuid()]);

        return $this;
    }

    /**
     * @param string $uuid
     *
     * @return AbstractPaymentMethod
     */
    public function get(string $uuid): AbstractPaymentMethod
    {
        return $this->collection[(string) $uuid];
    }

    /**
     * @return AbstractPaymentMethod[]
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * @return PaymentCollection
     */
    public function reset(): self
    {
        $this->collection = [];

        return $this;
    }
}
