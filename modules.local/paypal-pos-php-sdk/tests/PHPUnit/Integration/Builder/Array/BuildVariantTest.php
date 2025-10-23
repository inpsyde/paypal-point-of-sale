<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;
use Symfony\Component\Uid\Uuid;

class BuildVariantTest extends ZettlePhpSdkStandaloneTestCase
{
    use SetUpIdMapTrait;

    protected function setUp(): void
    {
        $this->setUpIdMap(ConnectionType::PRODUCT);
        $this->setUpIdMap(ConnectionType::VARIANT);
        $this->setUpIdMap(ConnectionType::IMAGE);

        // TODO: replace with option operator stub or mock after IZET-181 merge
        $this->injectFactory(
            'paypal-pos.sdk.config.woocommerce-config',
            function () {
                return null;
            }
        );

        parent::setUp();
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::variant()
     *
     * @param array $variantSampleData
     */
    public function testCreateEntityFromPayload(array $variantSampleData): void
    {
        $variant = $this->builder()->build(VariantInterface::class, $variantSampleData);

        $this->assertTrue(Uuid::isValid($variant->uuid()));
        $this->assertSame($variantSampleData['uuid'], $variant->uuid());
        $this->assertSame($variantSampleData['name'], $variant->name());
        $this->assertSame($variantSampleData['sku'], $variant->sku());
        $this->assertSame($variantSampleData['barcode'], $variant->barcode());

        $this->assertInstanceOf(Price::class, $variant->price());
        $this->assertInstanceOf(Price::class, $variant->costPrice());
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::variant()
     *
     * @param array $variantSampleData
     */
    public function testCreatePayloadFromEntity(array $variantSampleData): void
    {
        $variant = $this->builder()->build(VariantInterface::class, $variantSampleData);

        $variantPayload = $this->serializer()->serialize($variant);

        $this->assertIsArray($variantPayload);
        $this->assertNotEmpty($variantPayload);
        $this->assertCount(count($variantSampleData), $variantPayload);

        $this->assertEqualsCanonicalizing($variantSampleData, $variantPayload);
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
