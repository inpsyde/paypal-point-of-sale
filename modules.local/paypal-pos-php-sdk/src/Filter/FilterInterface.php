<?php

declare(strict_types=1);

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType

namespace Syde\PayPal\PointOfSale\PhpSdk\Filter;

interface FilterInterface
{
    /**
     * @param mixed $entity
     * @param mixed $payload
     *
     * @return bool
     */
    public function accepts($entity, $payload): bool;

    /**
     * @param mixed $entity
     * @param mixed $payload
     *
     * @return mixed
     */
    public function filter($entity, $payload);
}
