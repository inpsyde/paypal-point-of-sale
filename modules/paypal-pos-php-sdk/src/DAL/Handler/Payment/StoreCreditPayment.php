<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\StoreCreditPaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\StoreCreditPaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class StoreCreditPayment extends AbstractPaymentHandler
{
    private StoreCreditPaymentBuilder $storeCreditPaymentBuilder;
    /**
     * StoreCreditPayment constructor.
     *
     * @param StoreCreditPaymentBuilderInterface $storeCreditPaymentBuilder
     * @param string $validPaymentType
     */
    public function __construct(string $validPaymentType, StoreCreditPaymentBuilderInterface $storeCreditPaymentBuilder)
    {
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
