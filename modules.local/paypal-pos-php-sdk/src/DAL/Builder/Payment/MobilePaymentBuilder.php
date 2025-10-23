<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\MobilePayment;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\EntityFactoryException;

class MobilePaymentBuilder implements MobilePaymentBuilderInterface
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
    public function createDataArray(MobilePayment $mobilePayment): array
    {
        return [
            'uuid' => (string) $mobilePayment->uuid(),
            'amount' => $mobilePayment->amount(),
            'type' => $mobilePayment->type()->getValue(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): MobilePayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return MobilePayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): MobilePayment
    {
        return $this->paymentFactory->createMobilePayment(
            $data['uuid'],
            $data['type'],
            $data['amount']
        );
    }
}
