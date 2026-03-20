<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\KlarnaPayment;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentFactory;
class KlarnaPaymentBuilder implements KlarnaPaymentBuilderInterface
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
    public function createDataArray(KlarnaPayment $klarnaPayment): array
    {
        return ['uuid' => (string) $klarnaPayment->uuid(), 'type' => $klarnaPayment->type()->getValue(), 'amount' => $klarnaPayment->amount()];
    }
    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): KlarnaPayment
    {
        return $this->build($data);
    }
    private function build(array $data): KlarnaPayment
    {
        return $this->paymentFactory->createKlarnaPayment($data['uuid'], $data['type'], $data['amount']);
    }
}
