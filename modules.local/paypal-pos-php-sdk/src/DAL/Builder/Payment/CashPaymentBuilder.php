<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\CashPayment;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\EntityFactoryException;

class CashPaymentBuilder implements CashPaymentBuilderInterface
{
    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

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
    public function createDataArray(CashPayment $cashPayment): array
    {
        return [
            'cashPaymentUUID' => (string) $cashPayment->uuid(),
            'type' => $cashPayment->type()->getValue(),
            'amount' => $cashPayment->amount(),
            'attributes' => [
                'handedAmount' => $cashPayment->handedAmount(),
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): CashPayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return CashPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): CashPayment
    {
        return $this->paymentFactory->createCashPayment(
            $data['uuid'],
            $data['type'],
            $data['amount'],
            $data['attributes']['handedAmount']
        );
    }
}
