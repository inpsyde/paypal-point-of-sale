<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Presentation;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Throwable;

class ShortHexColorException extends Exception implements ValidatorException
{
    public function __construct(string $color, string $context, ?Throwable $previous = null)
    {
        parent::__construct(
            "{$context} contains short hex color {$color}",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::SHORT_HEX_COLOR];
    }
}
