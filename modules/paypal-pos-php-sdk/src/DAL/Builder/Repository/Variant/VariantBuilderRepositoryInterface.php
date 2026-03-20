<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Repository\Variant;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use WC_Product;
interface VariantBuilderRepositoryInterface
{
    /**
     * @param WC_Product $wcProduct
     * @param VariantCollection $collection
     *
     * @return VariantCollection
     *
     * @throws BuilderException If failed to build Variant from WC
     */
    public function addToCollection(WC_Product $wcProduct, VariantCollection $collection): VariantCollection;
}
