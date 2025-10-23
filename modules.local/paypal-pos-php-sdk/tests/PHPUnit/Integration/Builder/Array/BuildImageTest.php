<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class BuildImageTest extends ZettlePhpSdkStandaloneTestCase
{

    use AssertArraySimilarTrait;
    use SetUpIdMapTrait;

    protected function setUp(): void
    {
        $this->setUpNoopIdMaps();
        parent::setUp();
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::imageLookupKey()
     *
     * @param string $imageSampleData
     */
    public function testCreateEntityFromPayload(string $imageSampleData): void
    {
        $image = $this->builder()->build(ImageInterface::class, (array) $imageSampleData);
        $this->assertSame($imageSampleData, $image->imageLookupKey());
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::imageLookupKey()
     *
     * @param string $imageSampleData
     */
    public function testCreatePayloadFromEntity(string $imageSampleData): void
    {
        $image = $this->builder()->build(ImageInterface::class, (array) $imageSampleData);
        $imagePayload = current($this->serializer()->serialize($image));

        $this->assertIsString($imagePayload);
        $this->assertSame($image->imageLookupKey(), $imagePayload);
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
