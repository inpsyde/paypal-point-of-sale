<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaypalPayment;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\EntityFactoryException;
class PaypalPaymentBuilder implements PaypalPaymentBuilderInterface
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
    public function createDataArray(PaypalPayment $paypalPayment): array
    {
        return ['uuid' => (string) $paypalPayment->uuid(), 'amount' => $paypalPayment->amount(), 'type' => $paypalPayment->type()->getValue()];
    }
    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): PaypalPayment
    {
        return $this->build($data);
    }
    /**
     * @param array $data
     *
     * @return PaypalPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): PaypalPayment
    {
        return $this->paymentFactory->createPaypalPayment($data['uuid'], $data['type'], $data['amount']);
    }
}
