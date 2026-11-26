<?php

declare (strict_types=1);
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
// phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter;

interface FilterInterface
{
    public function accepts(mixed $entity, mixed $payload): bool;
    public function filter(mixed $entity, mixed $payload): mixed;
}
