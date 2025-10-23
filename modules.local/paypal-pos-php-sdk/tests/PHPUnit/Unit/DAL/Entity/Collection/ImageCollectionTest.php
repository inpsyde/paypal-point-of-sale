<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class ImageCollectionTest extends BrainMonkeyWpTestCase
{

    public function testCreateEmptyCollection(): void
    {
        $imageCollection = new ImageCollection();
        $this->assertEmpty($imageCollection->all());
    }

    public function testCreateCollectionWithData(): void
    {
        $keys = [
            'foo',
            'bar',
            'baz',
        ];
        $images = array_map(
            function (string $key): ImageInterface {
                $image = Mockery::mock(ImageInterface::class);
                $image->shouldReceive('imageLookupKey')->andReturn($key);
                return $image;
            },
            $keys
        );
        $imageCollection = new ImageCollection(...$images);

        $this->assertCount(count($keys), $imageCollection->all());

        foreach ($keys as $identifier) {
            $this->assertSame(
                $identifier,
                $imageCollection->get($identifier)->imageLookupKey()
            );
        }
    }

    public function testAddEntityToCollection(): void
    {
        $identifier = 'foo';
        $image = Mockery::mock(ImageInterface::class);
        $image->shouldReceive('imageLookupKey')->andReturn($identifier);
        $imageCollection = new ImageCollection();

        $this->assertEmpty($imageCollection->all());

        $imageCollection->add($image);

        $this->assertCount(1, $imageCollection->all());
        $this->assertSame(
            $image,
            $imageCollection->get($identifier)
        );
    }

    public function testRemoveEntityFromCollection(): void
    {
        $identifier = 'foo';
        $image = Mockery::mock(ImageInterface::class);
        $imageCollection = new ImageCollection($image);
        $this->assertNotEmpty($imageCollection->all());

        $imageCollection->remove($image);

        $this->assertEmpty($imageCollection->all());
    }

    public function testResetCollection(): void
    {
        $identifier = 'foo';
        $image = Mockery::mock(ImageInterface::class);
        $imageCollection = new ImageCollection($image);

        $this->assertNotEmpty($imageCollection->all());

        $imageCollection->reset();

        $this->assertEmpty($imageCollection->all());
    }
}
