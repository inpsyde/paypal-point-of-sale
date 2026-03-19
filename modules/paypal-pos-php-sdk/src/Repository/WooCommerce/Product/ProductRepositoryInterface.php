<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product;

use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductState;
use WC_Product;
use WC_Product_Variation;
interface ProductRepositoryInterface
{
    /**
     * @param int $id
     *
     * @return WC_Product|null
     */
    public function findById(int $id): ?WC_Product;
    /**
     * Return the parent product if it is a variation id, otherwise the same as findById.
     *
     * @param int $id
     *
     * @return WC_Product|null
     */
    public function findByIdOrVariationId(int $id): ?WC_Product;
    /**
     * @param WC_Product_Variation $variation
     *
     * @return WC_Product|null
     */
    public function findByVariation(WC_Product_Variation $variation): ?WC_Product;
    /**
     * @param int $variationId
     *
     * @return WC_Product|null
     */
    public function findByVariationId(int $variationId): ?WC_Product;
    /**
     * Fetch all Products from WooCommerce
     *
     * @param int $limit
     *
     * @return array
     */
    public function fetch(int $limit = -1): array;
    /**
     * Fetch specific type of Products
     *
     * @param array $types
     * @param string $status
     * @param int $limit
     *
     * @return int[]
     */
    public function fetchFromTypes(array $types, string $status = ProductState::PUBLISH, int $limit = -1): array;
}
