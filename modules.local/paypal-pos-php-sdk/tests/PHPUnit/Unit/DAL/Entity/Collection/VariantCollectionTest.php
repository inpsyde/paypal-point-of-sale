<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\Variant;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class VariantCollectionTest extends BrainMonkeyWpTestCase
{

    public function testCreateEmptyCollection(): void
    {
        $variantCollection = new VariantCollection();

        $this->assertIsArray($variantCollection->all());
        $this->assertEmpty($variantCollection->all());
    }

    public function testAddEntityToCollection(): void
    {
        $variantCollection = new VariantCollection();
        $this->assertEmpty($variantCollection->all());
        $uuid = uniqid('foo_');
        $variant = $this->mockVariant($uuid);
        $variantCollection->add($variant);

        $this->assertNotEmpty($variantCollection->all());
        $this->assertCount(1, $variantCollection->all());

        $this->assertSame(
            $uuid,
            $variantCollection->get($variant->uuid())->uuid()
        );
    }

    public function testRemoveEntityFromCollection(): void
    {
        $variant = $this->mockVariant();

        $variantCollection = new VariantCollection($variant);

        $this->assertNotEmpty($variantCollection->all());

        $variantCollection->remove($variant);

        $this->assertEmpty($variantCollection->all());
    }

    public function testGetEntityFromCollection(): void
    {
        $uuid = uniqid('foo_');
        $variant = $this->mockVariant($uuid);
        $variantCollection = new VariantCollection($variant);
        $this->assertNotEmpty($variantCollection->all());

        $variantFromCollection = $variantCollection->get($variant->uuid());

        $this->assertSame($uuid, $variantFromCollection->uuid());
    }

    public function testResetCollection(): void
    {
        $variantCollection = new VariantCollection($this->mockVariant());
        $this->assertNotEmpty($variantCollection->all());

        $variantCollection->reset();

        $this->assertEmpty($variantCollection->all());
    }

    private function mockVariant(string $id = null): Variant
    {
        $product = Mockery::mock(Variant::class);
        $product->shouldReceive('uuid')->andReturn($id ?? uniqid());

        return $product;
    }
}
