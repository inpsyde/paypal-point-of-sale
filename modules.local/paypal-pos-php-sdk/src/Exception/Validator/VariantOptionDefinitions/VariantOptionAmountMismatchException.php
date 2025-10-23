<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOptionDefinitions;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Throwable;

class VariantOptionAmountMismatchException extends Exception implements ValidatorException
{

    public function __construct(int $expected, int $amount, ?Throwable $previous = null)
    {
        parent::__construct(
            "Expected {$expected} VariantOptions but found {$amount}",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::VARIANT_OPTION_AMOUNT_MISMATCH];
    }
}
