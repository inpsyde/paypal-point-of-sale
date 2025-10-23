<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOption;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Throwable;

class MinimumVariantOptionNameCharacterLengthException extends Exception implements ValidatorException
{
    public function __construct(string $variantOptionName, int $minLength, ?Throwable $previous = null)
    {
        parent::__construct(
            "The given VariantOption {$variantOptionName} is too short,
            should be at least {$minLength} character long.",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::TOO_SHORT_VARIANT_NAME];
    }
}
