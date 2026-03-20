<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation;

use RuntimeException;
class ValidationFailedException extends RuntimeException
{
    /**
     * @var array
     */
    private $errors;
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
