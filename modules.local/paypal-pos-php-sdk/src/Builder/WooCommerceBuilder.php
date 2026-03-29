<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

use WC_Product;
use WC_Product_Attribute;
use WC_Product_Grouped;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

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

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null)
    {
        return $this->builder->build($className, $payload, $builder ?? $this);
    }

    /**
     * @param $payload
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     *
     * @return bool
     */
    public function accepts($payload): bool
    {
        return $payload instanceof WC_Product
            || $payload instanceof WC_Product_Variable
            || $payload instanceof WC_Product_Variation
            || $payload instanceof WC_Product_Simple
            || $payload instanceof WC_Product_Grouped
            || $payload instanceof WC_Product_Attribute;
    }
}
