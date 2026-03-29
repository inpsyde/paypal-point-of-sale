<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\CashPaymentBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\CashPaymentBuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;

class CashPaymentHandler extends AbstractPaymentHandler
{
    private CashPaymentBuilder $cashPaymentBuilder;

    /**
     * CashPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param CashPaymentBuilderInterface $cashPaymentBuilder
     */
    public function __construct(
        string $validPaymentType,
        CashPaymentBuilderInterface $cashPaymentBuilder
    ) {

        parent::__construct($validPaymentType);
        $this->cashPaymentBuilder = $cashPaymentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->cashPaymentBuilder->createDataArray($payment);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->cashPaymentBuilder->buildFromArray($data);
    }
}
