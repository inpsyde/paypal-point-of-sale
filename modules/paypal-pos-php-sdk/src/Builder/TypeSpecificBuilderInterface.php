<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

interface TypeSpecificBuilderInterface extends BuilderInterface
{
    /**
     * @param $payload
     * phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     * @return bool
     */
    public function accepts($payload): bool;
}
