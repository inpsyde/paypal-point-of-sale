<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
interface ValidatorInterface
{
    public function accepts(mixed $entity): bool;
    /**
     * @throws ValidatorException
     */
    public function validate(mixed $entity): bool;
}
