<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\StoreCreditPaymentBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\StoreCreditPaymentBuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;

class StoreCreditPayment extends AbstractPaymentHandler
{
    /**
     * @var StoreCreditPaymentBuilder
     */
    private $storeCreditPaymentBuilder;

    /**
     * StoreCreditPayment constructor.
     *
     * @param StoreCreditPaymentBuilderInterface $storeCreditPaymentBuilder
     * @param string $validPaymentType
     */
    public function __construct(
        string $validPaymentType,
        StoreCreditPaymentBuilderInterface $storeCreditPaymentBuilder
    ) {
        parent::__construct($validPaymentType);
        $this->storeCreditPaymentBuilder = $storeCreditPaymentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->storeCreditPaymentBuilder->createDataArray($payment);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->storeCreditPaymentBuilder->buildFromArray($data);
    }
}
