<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception;

interface ValidatorException extends BuilderException
{
    /**
     * Values of ValidationErrorCodes
     * @return string[]
     */
    public function errorCodes(): array;
}
