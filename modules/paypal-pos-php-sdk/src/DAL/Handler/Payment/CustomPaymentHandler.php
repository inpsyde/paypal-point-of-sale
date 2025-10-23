<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\CustomPaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\CustomPaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class CustomPaymentHandler extends AbstractPaymentHandler
{
    /**
     * @var CustomPaymentBuilder
     */
    private $customPaymentBuilder;
    /**
     * CustomPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param CustomPaymentBuilderInterface $customPaymentBuilder
     */
    public function __construct(string $validPaymentType, CustomPaymentBuilderInterface $customPaymentBuilder)
    {
        parent::__construct($validPaymentType);
        $this->customPaymentBuilder = $customPaymentBuilder;
    }
    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->customPaymentBuilder->createDataArray($payment);
    }
    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->customPaymentBuilder->buildFromArray($data);
    }
}
