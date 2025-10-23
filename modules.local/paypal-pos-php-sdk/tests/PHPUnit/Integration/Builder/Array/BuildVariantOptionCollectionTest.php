<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class BuildVariantOptionCollectionTest extends ZettlePhpSdkStandaloneTestCase
{

    use AssertArraySimilarTrait;
    use SetUpIdMapTrait;

    protected function setUp(): void
    {
        $this->setUpNoopIdMaps();
        parent::setUp();
    }
    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\VariantOptionSampleData::variantOptions()
     *
     * @param array $variantOptionsSampleData
     */
    public function testCreateCollectionFromPayload(array $variantOptionsSampleData): void
    {
        $variantOptionCollection = $this->builder()->build(
            VariantOptionCollection::class,
            $variantOptionsSampleData
        );

        $this->assertIsArray($variantOptionCollection->all());
        $this->assertNotEmpty($variantOptionCollection->all());
        $this->assertCount(count($variantOptionsSampleData), $variantOptionCollection->all());
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\VariantOptionSampleData::variantOptions()
     *
     * @param array $variantOptionsSampleData
     */
    public function testCreatePayloadFromCollection(array $variantOptionsSampleData): void
    {
        $optionCollection = $this->builder()->build(
            VariantOptionCollection::class,
            $variantOptionsSampleData
        );

        $variantOptionCollectionPayload = $this->serializer()->serialize($optionCollection);

        $this->assertIsArray($variantOptionCollectionPayload);
        $this->assertNotEmpty($variantOptionCollectionPayload);
        $this->assertCount(count($variantOptionsSampleData), $variantOptionCollectionPayload);
        $this->assertArraySimilar($variantOptionsSampleData, $variantOptionCollectionPayload);

        foreach ($variantOptionCollectionPayload as $variantOptionKey => $variantOptionPayload) {
            $this->assertIsArray($variantOptionPayload);
            $this->assertNotEmpty($variantOptionPayload);
            $this->assertCount(count($variantOptionsSampleData[$variantOptionKey]), $variantOptionPayload);
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
