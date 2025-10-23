<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOptionDefinitions;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Throwable;
// phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
class MaximumVariantOptionDefinitionsAmountException extends Exception implements ValidatorException
{
    /**
     * @param int $limit                Maximum amount of VariantOptionDefinitions
     * @param int $amount               VariantOptionDefinitions amount provided
     * @param Throwable|null $previous
     */
    public function __construct(int $limit, int $amount, ?Throwable $previous = null)
    {
        parent::__construct("Maximum amount of {$limit} VariantOptionDefinitions expected, {$amount} found", 0, $previous);
    }
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::TOO_MANY_VARIANT_OPTIONS];
    }
}
