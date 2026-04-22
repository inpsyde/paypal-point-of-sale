<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\AbstractBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Payment\InvalidPaymentTypeException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment\PaymentHandlerInterface;
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
    public function createDataArray(AbstractPaymentMethod $payment): array
    {
        if (!in_array($payment->type()->getValue(), PaymentType::getValidOptions(), \true)) {
            throw new InvalidPaymentTypeException(sprintf('Given Payment Entity has no valid Payment Type: %s', esc_html($payment->type()->getValue())));
        }
        foreach ($this->paymentHandlers as $paymentHandler) {
            if ($paymentHandler->accepts($payment->type()->getValue())) {
                return $paymentHandler->serialize($payment);
            }
        }
    }
    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): AbstractPaymentMethod
    {
        if (!in_array($data['type'], PaymentType::getValidOptions(), \true)) {
            throw new InvalidPaymentTypeException(sprintf('Given Payment Entity has no valid Payment Type: %s', esc_html($data['type'])));
        }
        foreach ($this->paymentHandlers as $paymentHandler) {
            if ($paymentHandler->accepts($data['type'])) {
                return $paymentHandler->deserialize($data);
            }
        }
    }
}
