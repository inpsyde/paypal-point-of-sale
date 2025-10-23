<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant;

use Generator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Util\WooCommerce\Attribute\AttributeAccessorUtilInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Util\WooCommerce\Attribute\AttributeFormatterUtilInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Util\WooCommerce\Variation\VariationAccessorUtilInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Util\WooCommerce\Variation\VariationCheckerUtilInterface;
use IteratorAggregate;
use WC_Product_Variable;
use WC_Product_Variation;
/**
 * Class WcVariationIterator
 *
 * Iterates over product attributes, generates all possible permutations,
 * and creates a Variant object for each of them
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant
 */
class WcVariationIterator implements IteratorAggregate
{
    /**
     * @var WC_Product_Variable
     */
    private $wcProduct;
    /**
     * @var BuilderInterface
     */
    private $builder;
    /**
     * @var ProductRepositoryInterface
     */
    private $repository;
    /**
     * WcVariationIterator constructor.
     *
     * @param WC_Product_Variable $wcProduct
     * @param BuilderInterface $builder
     * @param ProductRepositoryInterface $repository
     */
    public function __construct(WC_Product_Variable $wcProduct, BuilderInterface $builder, ProductRepositoryInterface $repository)
    {
        $this->wcProduct = $wcProduct;
        $this->builder = $builder;
        $this->repository = $repository;
    }
    /**
     * @inheritDoc
     *
     * TODO: Write Unit Test
     */
    public function getIterator(): Generator
    {
        foreach ($this->wcProduct->get_visible_children() as $wcProductVariantId) {
            $variation = $this->repository->findById($wcProductVariantId);
            if ($variation === null) {
                continue;
            }
            assert($variation instanceof WC_Product_Variation);
            if (!$variation->is_purchasable()) {
                continue;
            }
            $variationAttributes = (array) $variation->get_attributes();
            if (empty($variationAttributes)) {
                continue;
            }
            yield $wcProductVariantId => $this->builder->build(VariantInterface::class, $variation);
        }
    }
}
