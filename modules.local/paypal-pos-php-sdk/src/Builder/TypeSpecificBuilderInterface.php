<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

interface TypeSpecificBuilderInterface extends BuilderInterface
{
    /**
     * @param $payload
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     * @return bool
     */
    public function accepts($payload): bool;
}
