<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
final class InvalidImageSizeException extends Exception implements ValidatorException
{
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::INVALID_IMAGE_SIZE];
    }
}
