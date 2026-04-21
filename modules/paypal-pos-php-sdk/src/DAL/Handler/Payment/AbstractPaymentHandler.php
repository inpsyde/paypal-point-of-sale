<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Handler\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;
abstract class AbstractPaymentHandler implements PaymentHandlerInterface
{
    private string $validPaymentType;
    /**
     * AbstractPaymentHandler constructor.
     *
     * @param string $validPaymentType
     */
    public function __construct(string $validPaymentType)
    {
        $this->validPaymentType = $validPaymentType;
    }
    /**
     * @inheritDoc
     */
    public function accepts(string $paymentType): bool
    {
        return $paymentType === $this->validPaymentType;
    }
    /**
     * @inheritDoc
     */
    abstract public function serialize(AbstractPaymentMethod $payment): array;
    /**
     * @inheritDoc
     */
    abstract public function deserialize(array $data): AbstractPaymentMethod;
}
