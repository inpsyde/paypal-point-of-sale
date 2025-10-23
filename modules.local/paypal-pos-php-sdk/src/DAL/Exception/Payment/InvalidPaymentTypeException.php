<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Payment;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;

final class InvalidPaymentTypeException extends Exception implements ValidatorException
{
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::INVALID_PAYMENT_TYPE];
    }
}
