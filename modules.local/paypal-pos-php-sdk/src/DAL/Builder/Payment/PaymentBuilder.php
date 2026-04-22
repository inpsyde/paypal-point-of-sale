<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\AbstractBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Payment\InvalidPaymentTypeException;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment\PaymentHandlerInterface;

final class PaymentBuilder extends AbstractBuilder implements PaymentBuilderInterface
{
    /**
     * @var PaymentHandlerInterface[]
     */
    private array $paymentHandlers;

    public function __construct(PaymentHandlerInterface ...$paymentHandlers)
    {
        $this->paymentHandlers = $paymentHandlers;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(AbstractPaymentMethod $paymentMethod): array
    {
        if (!in_array($paymentMethod->type()->getValue(), PaymentType::getValidOptions(), true)) {
            throw new InvalidPaymentTypeException(sprintf(
                'Given Payment Entity has no valid Payment Type: %s',
                esc_html($paymentMethod->type()->getValue())
            ));
        }

        foreach ($this->paymentHandlers as $paymentHandler) {
            if ($paymentHandler->accepts($paymentMethod->type()->getValue())) {
                return $paymentHandler->serialize($paymentMethod);
            }
        }

        throw new InvalidPaymentTypeException('No Payment Handler for Payment Type: ' . esc_html($paymentMethod->type()->getValue()));
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): AbstractPaymentMethod
    {
        if (!in_array($data['type'], PaymentType::getValidOptions(), true)) {
            throw new InvalidPaymentTypeException('Given Payment Entity has no valid Payment Type: ' . esc_html($data['type']));
        }

        foreach ($this->paymentHandlers as $paymentHandler) {
            if ($paymentHandler->accepts($data['type'])) {
                return $paymentHandler->deserialize($data);
            }
        }

        throw new InvalidPaymentTypeException('No Payment Handler for Payment Type: ' . esc_html($data['type']));
    }
}
