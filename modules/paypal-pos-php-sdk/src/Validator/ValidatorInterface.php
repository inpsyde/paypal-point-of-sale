<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
interface ValidatorInterface
{
    /**
     * @param $entity
     *
     * @return bool
     */
    public function accepts($entity): bool;
    /**
     * @param $entity
     *
     * @return bool
     *
     * @throws ValidatorException
     */
    public function validate($entity): bool;
}
