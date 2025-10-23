<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class BuildVariantCollectionTest extends ZettlePhpSdkStandaloneTestCase
{
    use SetUpIdMapTrait;

    protected function setUp(): void
    {
        $this->setUpNoopIdMaps();

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
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::variants()
     *
     * @param array $variantsSampleData
     */
    public function testCreateCollectionFromPayload(array $variantsSampleData): void
    {
        $variantCollection = $this->builder()->build(
            VariantCollection::class,
            $variantsSampleData
        );
        $this->assertIsArray($variantCollection->all());
        $this->assertNotEmpty($variantCollection->all());
        $this->assertCount(count($variantsSampleData), $variantCollection->all());
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::variants()
     *
     * @param array $variantsSampleData
     */
    public function testCreatePayloadFromCollection(array $variantsSampleData): void
    {
        $variantCollection = $this->builder()->build(
            VariantCollection::class,
            $variantsSampleData
        );
        $variantCollectionPayload = $this->serializer()->serialize($variantCollection);

        $this->assertIsArray($variantCollectionPayload);
        $this->assertNotEmpty($variantCollectionPayload);
        $this->assertCount(count($variantsSampleData), $variantCollectionPayload);
        $this->assertEqualsCanonicalizing($variantsSampleData, $variantCollectionPayload);

        foreach ($variantCollectionPayload as $variantKey => $variantPayload) {
            $this->assertIsArray($variantPayload);
            $this->assertNotEmpty($variantPayload);
            $this->assertCount(count($variantsSampleData[$variantKey]), $variantPayload);
        }
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
