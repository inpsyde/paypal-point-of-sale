<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Factory;

// phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface as WcProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\Zettle\Product\ProductRepositoryInterface;
use WC_Product;
class WcProductFactory implements WcProductFactoryInterface
{
    private ProductRepositoryInterface $izProductRepository;
    private WcProductRepositoryInterface $wcProductRepository;
    public function __construct(ProductRepositoryInterface $izProductRepository, WcProductRepositoryInterface $wcProductRepository)
    {
        $this->izProductRepository = $izProductRepository;
        $this->wcProductRepository = $wcProductRepository;
    }
    /**
     * @inheritDoc
     */
    public function fromUuid(string $uuid): ?WC_Product
    {
        $productId = $this->izProductRepository->findByUuid($uuid);
        if ($productId === null) {
            return null;
        }
        $product = $this->wcProductRepository->findById($productId);
        if ($product === null) {
            return null;
        }
        return $product;
    }
}
