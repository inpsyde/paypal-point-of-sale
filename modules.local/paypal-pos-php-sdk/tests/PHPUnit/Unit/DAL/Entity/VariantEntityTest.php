<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\Variant;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class VariantEntityTest extends BrainMonkeyWpTestCase
{

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::variant()
     *
     * @param array $variantSampleData
     */
    public function testCreateEntity(array $variantSampleData): void
    {
        $name = 'foo';
        $description = 'Lorem ispum';
        $sku = 'bar';
        $price = Mockery::mock(Price::class);
        $defaultQty = 12;
        $uuid = uniqid();

        $variant = new Variant(
            $uuid,
            $name,
            $description,
            $sku,
            $defaultQty,
            $price
        );

        $this->assertSame($uuid, $variant->uuid());
        $this->assertSame($name, $variant->name());
        $this->assertSame($description, $variant->description());
        $this->assertSame($sku, $variant->sku());
        $this->assertInstanceOf(Price::class, $variant->price());
        $this->markTestIncomplete('Missing optional parameters');
    }
}
