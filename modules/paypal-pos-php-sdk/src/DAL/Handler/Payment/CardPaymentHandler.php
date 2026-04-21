<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\CardPaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\CardPaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class CardPaymentHandler extends AbstractPaymentHandler
{
    private CardPaymentBuilder $cardPaymentBuilder;
    /**
     * CardPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param CardPaymentBuilderInterface $cardPaymentBuilder
     */
    public function __construct(string $validPaymentType, CardPaymentBuilderInterface $cardPaymentBuilder)
    {
        parent::__construct($validPaymentType);
        $this->cardPaymentBuilder = $cardPaymentBuilder;
    }
    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->cardPaymentBuilder->createDataArray($payment);
    }
    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->cardPaymentBuilder->buildFromArray($data);
    }
}
