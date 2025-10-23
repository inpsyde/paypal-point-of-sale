<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Presentation\PresentationBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class PresentationEntityTest extends BrainMonkeyWpTestCase
{

    public function testSetBackgroundColor()
    {
        $image = Mockery::mock(ImageInterface::class);
        $testee = new Presentation($image, 'foo', 'bar');
        $this->assertSame('foo', $testee->backgroundColor());
        $testee->setBackgroundColor('baz');
        $this->assertSame('baz', $testee->backgroundColor());
    }

    public function testSetTextColor()
    {
        $image = Mockery::mock(ImageInterface::class);
        $testee = new Presentation($image, 'foo', 'bar');
        $this->assertSame('bar', $testee->textColor());
        $testee->setTextColor('baz');
        $this->assertSame('baz', $testee->textColor());
    }

    public function testSetImage()
    {
        $image = Mockery::mock(ImageInterface::class);
        $newImage = Mockery::mock(ImageInterface::class);
        $testee = new Presentation($image, 'foo', 'bar');
        $this->assertSame($image, $testee->image());
        $testee->setImage($newImage);
        $this->assertSame($newImage, $testee->image());
    }
}
