<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class VariantOptionCollectionTest extends BrainMonkeyWpTestCase
{

    public function testCreateEmptyCollection(): void
    {
        $variantOptionCollection = new VariantOptionCollection();

        $this->assertIsArray($variantOptionCollection->all());
        $this->assertEmpty($variantOptionCollection->all());
    }

    public function testCreateCollectionWithData(): void
    {
        $variantOptionCollection = new VariantOptionCollection($this->mockVariantOption());

        $this->assertNotEmpty($variantOptionCollection->all());
        $this->assertCount(1, $variantOptionCollection->all());
    }

    public function testAddEntityToCollection(): void
    {
        $variantOptionCollection = new VariantOptionCollection();
        $variantOptionCollection->add($this->mockVariantOption());

        $this->assertNotEmpty($variantOptionCollection->all());
        $this->assertCount(1, $variantOptionCollection->all());
    }

    public function testRemoveEntityFromCollection(): void
    {
        $variantOption = $this->mockVariantOption();
        $variantOptionCollection = new VariantOptionCollection($variantOption);
        $variantOptionCollection->remove($variantOption);
        $this->assertEmpty($variantOptionCollection->all());
    }

    public function testGetEntityFromCollection(): void
    {
        $variantOptionCollection = new VariantOptionCollection($this->mockVariantOption());

        $firstVariantOption = $this->mockVariantOption();
        $secondVariantOption = $this->mockVariantOption();

        $firstVariantOptionFromCollection = $variantOptionCollection->get($firstVariantOption);
        $secondVariantOptionFromCollection = $variantOptionCollection->get($secondVariantOption);

        $this->assertSame($firstVariantOption->name(), $firstVariantOptionFromCollection->name());
        $this->assertSame($firstVariantOption->value(), $firstVariantOptionFromCollection->value());

        $this->assertSame($secondVariantOption->name(), $secondVariantOptionFromCollection->name());
        $this->assertSame($secondVariantOption->value(), $secondVariantOptionFromCollection->value());
    }

    public function testResetCollection(): void
    {
        $variantOptionCollection = new VariantOptionCollection($this->mockVariantOption());
        $variantOptionCollection->reset();
        $this->assertEmpty($variantOptionCollection->all());
    }

    private function mockVariantOption(string $name = 'foo', string $value = 'bar'): VariantOption
    {
        $mock = Mockery::mock(VariantOption::class);
        $mock->shouldReceive('name')->andReturn($name);
        $mock->shouldReceive('value')->andReturn($value);

        return $mock;
    }
}
