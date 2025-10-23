<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOptionDefinitions;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Throwable;

class EmptyVariantOptionDefinitionsException extends Exception implements ValidatorException
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct(
            "Given Product requires VariantOptionDefinitions, but doesn't contains Definitions.",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::NO_VARIANT_OPTIONS];
    }
}
