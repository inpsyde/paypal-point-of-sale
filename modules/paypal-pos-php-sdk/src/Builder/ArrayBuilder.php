<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

/**
 * Class ArrayBuilder
 * Decorates another Builder, but registers it only for arrays
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\Builder
 */
class ArrayBuilder implements TypeSpecificBuilderInterface
{
    private BuilderInterface $builder;
    /**
     * @inheritDoc
     * phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    public function __construct(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }
    /**
     * @inheritDoc
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null)
    {
        return $this->builder->build($className, $payload, $builder ?? $this);
    }
    /**
     * @param $payload
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     *
     * @return bool
     */
    public function accepts($payload): bool
    {
        return is_array($payload);
    }
}
