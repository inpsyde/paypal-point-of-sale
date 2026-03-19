<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Validator\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Payment\InvalidPaymentTypeException;
class PaymentValidator
{
    /**
     * @param string $validPaymentType
     * @param string $paymentType
     *
     * @return bool
     *
     * @throws InvalidPaymentTypeException
     */
    public function validate(string $validPaymentType, string $paymentType): bool
    {
        if (!$this->validatePaymentType($validPaymentType, $paymentType)) {
            throw new InvalidPaymentTypeException(sprintf('Given Payment type: %s is not valid and doesnt match.', $paymentType));
        }
        return \true;
    }
    /**
     * @param string $validPaymentType
     * @param string $paymentType
     *
     * @return bool
     */
    private function validatePaymentType(string $validPaymentType, string $paymentType): bool
    {
        return $paymentType === $validPaymentType;
    }
}
