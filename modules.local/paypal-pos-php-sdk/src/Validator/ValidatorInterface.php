<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Validator;

// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType

use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;

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
