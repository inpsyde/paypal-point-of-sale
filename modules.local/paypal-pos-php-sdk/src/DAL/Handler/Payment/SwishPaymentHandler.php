<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\SwishPaymentBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\SwishPaymentBuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;

class SwishPaymentHandler extends AbstractPaymentHandler
{
    private SwishPaymentBuilder $swishPaymentBuilder;

    /**
     * SwishPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param SwishPaymentBuilderInterface $swishPaymentBuilder
     */
    public function __construct(
        string $validPaymentType,
        SwishPaymentBuilderInterface $swishPaymentBuilder
    ) {

        parent::__construct($validPaymentType);
        $this->swishPaymentBuilder = $swishPaymentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->swishPaymentBuilder->createDataArray($payment);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->swishPaymentBuilder->buildFromArray($data);
    }
}
