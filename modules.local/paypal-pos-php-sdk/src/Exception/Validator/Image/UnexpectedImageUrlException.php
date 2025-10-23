<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;

class UnexpectedImageUrlException extends Exception implements ValidatorException
{
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::UNEXPECTED_IMAGE_URL];
    }
}
