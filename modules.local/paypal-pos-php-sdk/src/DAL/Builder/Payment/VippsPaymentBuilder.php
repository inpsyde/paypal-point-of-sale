<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\VippsPayment;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\EntityFactoryException;

class VippsPaymentBuilder implements VippsPaymentBuilderInterface
{
    private PaymentFactory $paymentFactory;

    /**
     * CardOnlinePaymentBuilder constructor.
     *
     * @param PaymentFactory $paymentFactory
     */
    public function __construct(PaymentFactory $paymentFactory)
    {
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(VippsPayment $vippsPayment): array
    {
        return [
            'uuid' => (string) $vippsPayment->uuid(),
            'amount' => $vippsPayment->amount(),
            'type' => $vippsPayment->type()->getValue(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): VippsPayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return VippsPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): VippsPayment
    {
        return $this->paymentFactory->createVippsPayment(
            $data['uuid'],
            $data['type'],
            $data['amount']
        );
    }
}
