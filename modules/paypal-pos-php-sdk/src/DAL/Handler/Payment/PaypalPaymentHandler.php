<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\PaypalPaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\PaypalPaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class PaypalPaymentHandler extends AbstractPaymentHandler
{
    /**
     * @var PaypalPaymentBuilder
     */
    private $paypalPaymentBuilder;
    /**
     * PaypalPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param PaypalPaymentBuilderInterface $paypalPaymentBuilder
     */
    public function __construct(string $validPaymentType, PaypalPaymentBuilderInterface $paypalPaymentBuilder)
    {
        parent::__construct($validPaymentType);
        $this->paypalPaymentBuilder = $paypalPaymentBuilder;
    }
    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->paypalPaymentBuilder->createDataArray($payment);
    }
    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->paypalPaymentBuilder->buildFromArray($data);
    }
}
