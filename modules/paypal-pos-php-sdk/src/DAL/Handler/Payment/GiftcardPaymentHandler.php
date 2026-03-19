<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\GiftcardPaymentBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment\GiftcardPaymentBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
class GiftcardPaymentHandler extends AbstractPaymentHandler
{
    /**
     * @var GiftcardPaymentBuilder
     */
    private $giftcardPaymentBuilder;
    /**
     * GiftcardPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param GiftcardPaymentBuilderInterface $giftcardPaymentBuilder
     */
    public function __construct(string $validPaymentType, GiftcardPaymentBuilderInterface $giftcardPaymentBuilder)
    {
        parent::__construct($validPaymentType);
        $this->giftcardPaymentBuilder = $giftcardPaymentBuilder;
    }
    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->giftcardPaymentBuilder->createDataArray($payment);
    }
    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->giftcardPaymentBuilder->buildFromArray($data);
    }
}
