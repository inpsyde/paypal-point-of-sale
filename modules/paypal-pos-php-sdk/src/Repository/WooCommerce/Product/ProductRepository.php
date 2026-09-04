<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product;

use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductState;
use WC_Product;
use WC_Product_Variation;
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function findById(int $id): ?WC_Product
    {
        $product = wc_get_product($id);
        if (!$product) {
            return null;
        }
        return $product;
    }
    /**
     * @inheritDoc
     */
    public function findByIdOrVariationId(int $id): ?WC_Product
    {
        $product = $this->findById($id);
        if (!$product) {
            return null;
        }
        if ($product instanceof WC_Product_Variation) {
            return $this->findByVariation($product);
        }
        return $product;
    }
    /**
     * @inheritDoc
     */
    public function findByVariation(WC_Product_Variation $variation): ?WC_Product
    {
        return $this->findById((int) $variation->get_parent_id());
    }
    /**
     * @inheritDoc
     */
    public function findByVariationId(int $variationId): ?WC_Product
    {
        $variation = $this->findById($variationId);
        if ($variation === null) {
            return null;
        }
        assert($variation instanceof WC_Product_Variation);
        $product = $this->findByVariation($variation);
        if ($product === null) {
            return null;
        }
        return $product;
    }
    /**
     * @inheritDoc
     */
    public function fetch(int $limit = -1): array
    {
        $result = wc_get_products(['limit' => $limit, 'return' => 'ids']);
        return is_array($result) ? $result : [];
    }
    /**
     * @inheritDoc
     */
    public function fetchFromTypes(array $types, string $status = ProductState::PUBLISH, int $limit = -1): array
    {
        $result = wc_get_products(['type' => $types, 'status' => $status, 'limit' => $limit, 'return' => 'ids']);
        return is_array($result) ? $result : [];
    }
}
