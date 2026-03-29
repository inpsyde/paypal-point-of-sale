<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\MobilePaymentBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\MobilePaymentBuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;

class MobilePaymentHandler extends AbstractPaymentHandler
{
    private MobilePaymentBuilder $mobilePaymentBuilder;

    /**
     * MobilePaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param MobilePaymentBuilderInterface $mobilePaymentBuilder
     */
    public function __construct(
        string $validPaymentType,
        MobilePaymentBuilderInterface $mobilePaymentBuilder
    ) {

        parent::__construct($validPaymentType);
        $this->mobilePaymentBuilder = $mobilePaymentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->mobilePaymentBuilder->createDataArray($payment);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->mobilePaymentBuilder->buildFromArray($data);
    }
}
