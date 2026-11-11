<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOption;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Throwable;
class MaximumVariantOptionNameCharacterLengthException extends Exception implements ValidatorException
{
    public function __construct(string $variantOptionName, int $maxLength, ?Throwable $previous = null)
    {
        parent::__construct("The given VariantOption {$variantOptionName} is too long,\n            should be maximum {$maxLength} characters long", 0, $previous);
    }
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::TOO_LONG_VARIANT_NAME];
    }
}
