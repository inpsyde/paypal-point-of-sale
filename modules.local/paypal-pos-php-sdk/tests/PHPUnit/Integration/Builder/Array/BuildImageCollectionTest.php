<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class BuildImageCollectionTest extends ZettlePhpSdkStandaloneTestCase
{

    use AssertArraySimilarTrait;
    use SetUpIdMapTrait;
    protected function setUp(): void
    {
        $this->setUpIdMap(ConnectionType::PRODUCT);
        $this->setUpIdMap(ConnectionType::VARIANT);
        $this->setUpIdMap(ConnectionType::IMAGE);

        parent::setUp();
    }

    public function testCreateImageCollectionFromPayload(): void
    {
        $keys = [
            'foo',
            'bar',
            'baz',
        ];
        $imageCollection = $this->builder()->build(ImageCollection::class, $keys);

        $this->assertIsArray($imageCollection->all());
        $this->assertNotEmpty($imageCollection->all());
        $this->assertCount(count($keys), $imageCollection->all());

        foreach ($keys as $imageLookupKey) {
            $this->assertSame(
                $imageLookupKey,
                $imageCollection->get($imageLookupKey)->imageLookupKey()
            );
        }
    }

    public function testCreatePayloadFromImageCollection()
    {
        $keys = [
            'foo',
            'bar',
            'baz',
        ];
        $builder = $this->builder();
        $imageCollection = $builder->build(ImageCollection::class, $keys);

        $imageCollectionPayload = $this->serializer()->serialize($imageCollection);

        $this->assertCount(count($keys), $imageCollectionPayload);
        $this->assertArraySimilar($keys, $imageCollectionPayload);
    }

    /** @return SerializerInterface */
    public function serializer(): SerializerInterface
    {
        return $this->get('paypal-pos.sdk.serializer');
    }

    /** @return BuilderInterface */
    public function builder(): BuilderInterface
    {
        return $this->get('paypal-pos.sdk.builder');
    }
}
