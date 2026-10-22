<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use WC_Product;
use WC_Product_Attribute;
/**
 * Class WooCommerceBuilder
 * Decorates another builder, but registers if only for WooCommerce types
 * @package Syde\PayPal\PointOfSale\PhpSdk\Builder
 */
class WooCommerceBuilder implements TypeSpecificBuilderInterface
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
        return $payload instanceof WC_Product || $payload instanceof WC_Product_Attribute;
    }
}
