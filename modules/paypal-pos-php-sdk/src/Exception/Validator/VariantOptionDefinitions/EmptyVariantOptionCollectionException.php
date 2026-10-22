<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOptionDefinitions;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Throwable;
class EmptyVariantOptionCollectionException extends Exception implements ValidatorException
{
    public function __construct(array $emptyOptions, ?Throwable $previous = null)
    {
        $emptyOptionsFormatted = implode(', ', $emptyOptions);
        parent::__construct("The given VariantOptionDefinitions contain empty VariantOptions: {$emptyOptionsFormatted}", 0, $previous);
    }
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::NO_VARIANT_OPTIONS];
    }
}
