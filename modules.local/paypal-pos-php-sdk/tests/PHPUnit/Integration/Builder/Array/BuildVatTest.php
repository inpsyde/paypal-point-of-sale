<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class BuildVatTest extends ZettlePhpSdkStandaloneTestCase
{

    use AssertArraySimilarTrait;
    use SetUpIdMapTrait;

    protected function setUp(): void
    {
        $this->setUpNoopIdMaps();
        parent::setUp();
    }
    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::vat()
     *
     * @param float $vatSampleData
     */
    public function testCreateEntityFromPayload(float $vatSampleData): void
    {
        $vat = $this->builder()->build(Vat::class, (array) $vatSampleData);

        $this->assertSame($vatSampleData, $vat->percentage());
        $this->assertIsFloat($vat->percentage());
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::vat()
     *
     * @param $vatSampleData
     */
    public function testCreatePayloadFromPayload(float $vatSampleData): void
    {
        $vat = $this->builder()->build(Vat::class, (array) $vatSampleData);

        $vatPayload = $this->serializer()->serialize($vat);

        $this->assertIsArray($vatPayload);
        $this->assertNotEmpty($vatPayload);
        $this->assertCount(count([$vatSampleData]), $vatPayload);

        $this->assertSame($vatSampleData, $vatPayload['vatPercentage']);
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
