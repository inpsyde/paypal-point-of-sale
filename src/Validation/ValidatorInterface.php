<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Validation;

interface ValidatorInterface
{
    /**
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * @param mixed $value
     */
    public function validate($value): void;
}
