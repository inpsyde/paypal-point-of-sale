<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Presentation;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Throwable;

class InvalidHexColorException extends Exception implements ValidatorException
{
    public function __construct(string $color, ?Throwable $previous = null)
    {
        parent::__construct(
            "Presentation contains invalid hex color {$color}",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::INVALID_HEX_COLOR];
    }
}
