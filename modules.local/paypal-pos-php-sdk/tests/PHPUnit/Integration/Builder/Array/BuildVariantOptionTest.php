<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class BuildVariantOptionTest extends ZettlePhpSdkStandaloneTestCase
{

    use AssertArraySimilarTrait;
    use SetUpIdMapTrait;

    protected function setUp(): void
    {
        $this->setUpNoopIdMaps();
        parent::setUp();
    }
    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\VariantOptionSampleData::variantOption()
     *
     * @param array $variantOptionSampleData
     */
    public function testCreateEntityFromPayload(array $variantOptionSampleData): void
    {
        $variantOption = $this->builder()->build(VariantOption::class, $variantOptionSampleData);

        $this->assertSame($variantOptionSampleData['name'], $variantOption->name());
        $this->assertSame($variantOptionSampleData['value'], $variantOption->value());
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\VariantOptionSampleData::variantOption()
     *
     * @param array $variantOptionSampleData
     */
    public function testCreatePayloadFromEntity(array $variantOptionSampleData): void
    {
        $variantOption = $this->builder()->build(VariantOption::class, $variantOptionSampleData);

        $variantOptionPayload = $this->serializer()->serialize($variantOption);

        $this->assertIsArray($variantOptionPayload);
        $this->assertNotEmpty($variantOptionPayload);
        $this->assertCount(count($variantOptionSampleData), $variantOptionPayload);

        $this->assertSame($variantOptionSampleData, $variantOptionPayload);
        $this->assertSame($variantOptionSampleData['name'], $variantOptionPayload['name']);
        $this->assertSame($variantOptionSampleData['value'], $variantOptionPayload['value']);

        $this->assertArraySimilar($variantOptionSampleData, $variantOptionPayload);
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
