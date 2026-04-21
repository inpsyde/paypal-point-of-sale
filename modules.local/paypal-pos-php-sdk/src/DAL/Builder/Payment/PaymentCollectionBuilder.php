<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentCollectionFactory;

class PaymentCollectionBuilder implements PaymentCollectionBuilderInterface
{
    private PaymentCollectionFactory $paymentCollectionFactory;

    private PaymentBuilderInterface $paymentBuilder;

    /**
     * PaymentCollectionBuilder constructor.
     *
     * @param PaymentCollectionFactory $paymentCollectionFactory
     * @param PaymentBuilderInterface $paymentBuilder
     */
    public function __construct(
        PaymentCollectionFactory $paymentCollectionFactory,
        PaymentBuilderInterface $paymentBuilder
    ) {

        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->paymentBuilder = $paymentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(PaymentCollection $paymentCollection): array
    {
        $data = [];

        foreach ($paymentCollection->all() as $payment) {
            $data[][] = $this->paymentBuilder->createDataArray($payment);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): PaymentCollection
    {
        $paymentCollection = $this->paymentCollectionFactory->create();

        foreach ($data as $payment) {
            $paymentCollection->add(
                $this->paymentBuilder->buildFromArray($payment)
            );
        }

        return $paymentCollection;
    }
}
