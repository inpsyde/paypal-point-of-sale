<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product;

use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductState;
use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductType;
use WC_Product;
use WC_Product_Variation;
class ArrayProductRepository implements ProductRepositoryInterface
{
    /**
     * @var array<int, WC_Product>
     */
    private array $repository;
    /**
     * ArrayProductRepository constructor.
     *
     * @param array $repository
     */
    public function __construct(array $repository = [])
    {
        $this->repository = $repository;
    }
    public function findById(int $id): ?WC_Product
    {
        return $this->repository[$id] ?? null;
    }
    /**
     * @inheritDoc
     */
    public function findByIdOrVariationId(int $id): ?WC_Product
    {
        $product = $this->findById($id);
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
        return $this->findById($variation->get_parent_id());
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
        if (!$variation->is_type(ProductType::VARIATION)) {
            return null;
        }
        assert($variation instanceof WC_Product_Variation);
        return $this->findByVariation($variation);
    }
    /**
     * @inheritDoc
     */
    public function fetch(int $limit = -1): array
    {
        // TODO: Limit logic
        return $this->repository;
    }
    /**
     * @inheritDoc
     */
    public function fetchFromTypes(array $types, string $status = ProductState::PUBLISH, int $limit = -1): array
    {
        $ids = [];
        foreach ($this->repository as $id => $product) {
            assert($product instanceof WC_Product);
            if (!in_array($product->get_type(), $types, \true)) {
                continue;
            }
            if ($product->get_status() !== $status) {
                continue;
            }
            if ($limit !== -1 && count($ids) >= $limit) {
                return $ids;
            }
            $ids[] = (int) $id;
        }
        return $ids;
    }
}
