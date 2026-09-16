<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter\FilterInterface;
/**
 * Class FilterableBuilder
 * Decorates another Builder and runs its result through a Filter
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\Builder
 */
class FilterableBuilder implements BuilderInterface
{
    private BuilderInterface $builder;
    private FilterInterface $filter;
    public function __construct(BuilderInterface $builder, FilterInterface $filter)
    {
        $this->builder = $builder;
        $this->filter = $filter;
    }
    public function build(string $className, mixed $payload, ?BuilderInterface $builder = null): mixed
    {
        $result = $this->builder->build($className, $payload, $builder ?? $this);
        if (!$this->filter->accepts($result, $payload)) {
            return $result;
        }
        return $this->filter->filter($result, $payload);
    }
}
