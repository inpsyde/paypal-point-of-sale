<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\GiftCardPayment;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\EntityFactoryException;

class GiftcardPaymentBuilder implements GiftcardPaymentBuilderInterface
{
    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * CardOnlinePaymentBuilder constructor.
     *
     * @param PaymentFactory $paymentFactory
     */
    public function __construct(PaymentFactory $paymentFactory)
    {
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(GiftCardPayment $giftCardPayment): array
    {
        return [
            'uuid' => (string) $giftCardPayment->uuid(),
            'type' => $giftCardPayment->type()->getValue(),
            'amount' => $giftCardPayment->amount(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): GiftCardPayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return GiftCardPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): GiftCardPayment
    {
        return $this->paymentFactory->createGiftcardPayment(
            $data['uuid'],
            $data['type'],
            $data['amount']
        );
    }
}
