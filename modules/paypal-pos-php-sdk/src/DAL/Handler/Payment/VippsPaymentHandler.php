<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\VippsPaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\VippsPaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class VippsPaymentHandler extends AbstractPaymentHandler
{
    /**
     * @var VippsPaymentBuilder
     */
    private $vippsPaymentBuilder;
    /**
     * VippsPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param VippsPaymentBuilderInterface $vippsPaymentBuilder
     */
    public function __construct(string $validPaymentType, VippsPaymentBuilderInterface $vippsPaymentBuilder)
    {
        parent::__construct($validPaymentType);
        $this->vippsPaymentBuilder = $vippsPaymentBuilder;
    }
    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->vippsPaymentBuilder->createDataArray($payment);
    }
    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->vippsPaymentBuilder->buildFromArray($data);
    }
}
