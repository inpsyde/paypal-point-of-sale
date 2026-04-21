<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Validation;

use RuntimeException;

class ValidationFailedException extends RuntimeException
{
    private array $errors;

    public function __construct(string $message = '', array $errors = [])
    {
        parent::__construct($message);

        $this->errors = $errors;
    }

    public function getValidationErrors(): array
    {
        return $this->errors;
    }
}
