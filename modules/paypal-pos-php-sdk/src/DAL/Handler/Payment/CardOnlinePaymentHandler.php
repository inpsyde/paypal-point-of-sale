<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\CardOnlinePaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\CardOnlinePaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class CardOnlinePaymentHandler extends AbstractPaymentHandler
{
    private CardOnlinePaymentBuilder $cardOnlinePaymentBuilder;
    /**
     * CardOnlinePaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param CardOnlinePaymentBuilderInterface $cardOnlinePaymentBuilder
     */
    public function __construct(string $validPaymentType, CardOnlinePaymentBuilderInterface $cardOnlinePaymentBuilder)
    {
        parent::__construct($validPaymentType);
        $this->cardOnlinePaymentBuilder = $cardOnlinePaymentBuilder;
    }
    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->cardOnlinePaymentBuilder->createDataArray($payment);
    }
    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->cardOnlinePaymentBuilder->buildFromArray($data);
    }
}
