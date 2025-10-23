<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class VariantOptionEntityTest extends BrainMonkeyWpTestCase
{

    public function testCreateEntity(): void
    {
        $name = 'foo';
        $value = 'bar';
        $variantOption = new VariantOption($name, $value);

        $this->assertSame($name, $variantOption->name());
        $this->assertSame($value, $variantOption->value());
        $this->assertNull($variantOption->image());
    }

    public function testCreateEntityWithImage(): void
    {
        $name = 'foo';
        $value = 'bar';
        $image = Mockery::mock(ImageInterface::class);
        $variantOption = new VariantOption($name, $value, $image);

        $this->assertSame($name, $variantOption->name());
        $this->assertSame($value, $variantOption->value());
        $this->assertSame($image, $variantOption->image());
    }
}
