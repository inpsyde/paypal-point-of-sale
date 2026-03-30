<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\LazyProduct;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\Product;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductTransferInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use WC_Product;
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
/**
 * Class ProductConnectionFilter
 *
 * This filter connects an Zettle Product with its WooCommerce counterpart
 * by querying an ID mapping table
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\Filter
 */
class ProductConnectionFilter implements FilterInterface
{
    private OneToOneMapInterface $idMap;
    private $lazyPool = [];
    /**
     * @var callable
     */
    private $productClientProvider;
    public function __construct(OneToOneMapInterface $idMap, callable $productClientProvider)
    {
        $this->idMap = $idMap;
        $this->productClientProvider = $productClientProvider;
        add_action('paypal-pos.clear-product-cache', function () {
            $this->lazyPool = [];
        });
    }
    /**
     * @inheritDoc
     */
    public function accepts($entity, $payload): bool
    {
        return $entity instanceof Product and $payload instanceof WC_Product;
    }
    /**
     * @inheritDoc
     */
    public function filter($product, $wcProduct)
    {
        assert($wcProduct instanceof WC_Product);
        assert($product instanceof ProductTransferInterface);
        $localId = $wcProduct->get_id();
        try {
            $remoteId = $this->idMap->remoteId($localId);
            $product->setUuid($remoteId);
        } catch (IdNotFoundException $exception) {
            $product = $this->getLazyProduct($localId, $product);
        }
        return $product;
    }
    private function getLazyProduct(int $localId, ProductTransferInterface $product): ProductInterface
    {
        if (!isset($this->lazyPool[$localId])) {
            $productClient = ($this->productClientProvider)();
            $this->lazyPool[$localId] = new LazyProduct($localId, $product, $productClient, $this->idMap);
        }
        return $this->lazyPool[$localId];
    }
}
