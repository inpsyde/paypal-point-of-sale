<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\Product;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductTransferInterface;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class ProductCollectionTest extends BrainMonkeyWpTestCase
{

    public function testCreateEmptyCollection(): void
    {
        $productCollection = new ProductCollection();
        $this->assertEmpty($productCollection->all());
    }

    public function testAddEntityToCollection(): void
    {
        $id = uniqid('foo_');
        $product = $this->mockProduct($id);
        $productCollection = new ProductCollection();
        $productCollection->add($product);
        $this->assertSame(
            $id,
            $productCollection->get($product->uuid())->uuid()
        );
    }

    public function testRemoveEntityFromCollection(): void
    {
        $product = $this->mockProduct();
        $productCollection = new ProductCollection($product);

        $this->assertNotEmpty($productCollection->all());

        $productCollection->remove($product);

        $this->assertEmpty($productCollection->all());
    }

    public function testGetEntityFromCollection(): void
    {
        $uuid = uniqid('foo_');
        $product = $this->mockProduct($uuid);
        $productCollection = new ProductCollection($product);

        $this->assertNotEmpty($productCollection->all());

        $this->assertSame(
            $uuid,
            $productCollection->get($product->uuid())->uuid()
        );
    }

    public function testResetCollection(): void
    {
        $productCollection = new ProductCollection($this->mockProduct());

        $this->assertNotEmpty($productCollection->all());

        $this->assertIsArray($productCollection->all());
        $this->assertNotEmpty($productCollection->all());

        $productCollection->reset();

        $this->assertEmpty($productCollection->all());
    }

    private function mockProduct(string $id = null): ProductTransferInterface
    {
        $product = Mockery::mock(Product::class);
        $product->shouldReceive('uuid')->andReturn($id ?? uniqid());

        return $product;
    }
}
