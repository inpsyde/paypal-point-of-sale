<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator\ProductValidator;
use Throwable;
final class MaximumVariantsException extends Exception implements ValidatorException
{
    public function __construct(string $productName, int $variantsAmount, int $code = 0, ?Throwable $previous = null)
    {
        $maxVariants = ProductValidator::MAXIMUM_VARIANTS_AMOUNT;
        parent::__construct("The Product: {$productName} has mor than {$maxVariants}, contains: {$variantsAmount}", $code, $previous);
    }
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::TOO_MANY_VARIANTS];
    }
}
