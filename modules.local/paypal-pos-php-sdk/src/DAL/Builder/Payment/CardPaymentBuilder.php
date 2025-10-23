<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\AbstractBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\CardPayment;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\EntityFactoryException;

class CardPaymentBuilder extends AbstractBuilder implements CardPaymentBuilderInterface
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
    public function createDataArray(CardPayment $cardPayment): array
    {
        $data = [
            'cardPaymentUUID' => (string) $cardPayment->uuid(),
            'amount' => $cardPayment->amount(),
            'type' => $cardPayment->type()->getValue(),
            'attributes' => [
                'transactionStatusInformation' => '',
                'cardPaymentEntryMode' => $cardPayment->cardPaymentEntryMode(),
                'maskedPan' => $cardPayment->maskedPan(),
                'referenceNumber' => $cardPayment->referenceNumber(),
                'cardType' => $cardPayment->cardType(),
            ],
        ];

        if ($cardPayment->applicationName()) {
            $data['attributes']['applicationName'] = $cardPayment->applicationName();
        }

        if ($cardPayment->applicationIdentifier()) {
            $data['attributes']['applicationIdentifier'] = $cardPayment->applicationIdentifier();
        }

        if ($cardPayment->terminalVerificationResults()) {
            $data['attributes']['terminalVerificationResults'] = $cardPayment->terminalVerificationResults();
        }

        if ($cardPayment->numberOfInstallments()) {
            $data['attributes']['nrOfInstallments'] = $cardPayment->numberOfInstallments();
        }

        if ($cardPayment->installmentAmount()) {
            $data['attributes']['installmentAmount'] = $cardPayment->installmentAmount();
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): CardPayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return CardPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): CardPayment
    {
        return $this->paymentFactory->createCardPayment(
            $data['uuid'],
            (float) $data['amount'],
            $data['attributes']['referenceNumber'],
            $data['attributes']['maskedPan'],
            $data['attributes']['cardType'],
            $data['attributes']['cardPaymentEntryMode'],
            $this->getDataFromKey('applicationName', $data['attributes']),
            $this->getDataFromKey('applicationIdentifier', $data['attributes']),
            $this->getDataFromKey('terminalVerificationResults', $data['attributes']),
            (int) $this->getDataFromKey('nrOfInstallments', $data['attributes'])
        );
    }
}
