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
    public function __construct(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }
    public function build(string $className, mixed $payload, ?BuilderInterface $builder = null): mixed
    {
        return $this->builder->build($className, $payload, $builder ?? $this);
    }
    public function accepts(mixed $payload): bool
    {
        return is_array($payload);
    }
}
