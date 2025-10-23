<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Inventory\Inventory;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\Variant;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantInventoryState\VariantInventoryState;
use Syde\PayPal\PointOfSale\PhpSdk\Map\InMemoryMap;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\PayPal\PointOfSale\Test\DataProvider\ProductSampleData;
use Syde\PayPal\PointOfSale\Test\ZettleEntityCrudTestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @group sync
 */
class InventoryTrackingTest extends ZettleEntityCrudTestCase
{

    protected function setUp(): void
    {
        $this->injectFactory(
            'paypal-pos.sdk.id-map.product',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );
        $this->injectFactory(
            'paypal-pos.sdk.id-map.variant',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );
        $this->injectFactory(
            'paypal-pos.sdk.id-map.image',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );
        $this->injectFactory(
            'paypal-pos.sdk.integration-id',
            function (ContainerInterface $container): string {
                return (string) Uuid::v1();
            }
        );

        parent::setUp();
    }

    public function testInventoryTracking()
    {
        $builder = $this->builder();
        $product = $builder->build(ProductInterface::class, ProductSampleData::sampleProductData());
        $variant = current($product->variants()->all());
        assert($variant instanceof Variant);
        $productsApi = $this->productsApi();
        $productsApi->create($product);
        $inventory = $this->inventory();

        $inventory->startTracking($product->uuid());

        // Remove 10 items from stock
        $inventory->purchase(
            $product->uuid(),
            $variant->uuid(),
            10
        );

        // Add 10 items from stock
        $inventory->supply(
            $product->uuid(),
            $variant->uuid(),
            10
        );

        $inventory->stopTracking($product->uuid());

        // Remove products created during this test
        $list = $productsApi->list();
        $deleteSuccess = $productsApi->deleteBulk($list->all());
        $this->assertTrue($deleteSuccess);
    }

    private function productsApi(): Products
    {
        return $this->get('paypal-pos.sdk.api.products');
    }

    private function builder(): BuilderInterface
    {
        return $this->get('paypal-pos.sdk.builder');
    }

    private function inventory(): Inventory
    {
        return $this->get('paypal-pos.sdk.api.inventory');
    }
}
