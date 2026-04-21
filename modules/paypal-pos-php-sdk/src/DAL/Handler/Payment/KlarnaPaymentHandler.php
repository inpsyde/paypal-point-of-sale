<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\KlarnaPaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\KlarnaPaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class KlarnaPaymentHandler extends AbstractPaymentHandler
{
    private KlarnaPaymentBuilder $klarnaPaymentBuilder;
    /**
     * KlarnaPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param KlarnaPaymentBuilderInterface $klarnaPaymentBuilder
     */
    public function __construct(string $validPaymentType, KlarnaPaymentBuilderInterface $klarnaPaymentBuilder)
    {
        parent::__construct($validPaymentType);
        $this->klarnaPaymentBuilder = $klarnaPaymentBuilder;
    }
    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->klarnaPaymentBuilder->createDataArray($payment);
    }
    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->klarnaPaymentBuilder->buildFromArray($data);
    }
}
