<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class BuildPriceTest extends ZettlePhpSdkStandaloneTestCase
{

    use AssertArraySimilarTrait;
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
     * @dataProvider defaultTestData
     *
     * @param array $priceData
     */
    public function testCreateEntityFromPayload(array $priceData): void
    {
        $price = $this->builder()->build(Price::class, $priceData);
        $expectedPrice = (int) $priceData['amount'];
        $this->assertSame($expectedPrice, $price->amount());
        $this->assertSame($priceData['currencyId'], $price->currencyId());
        $this->assertIsString($price->currencyId());
    }

    /**
     * @dataProvider defaultTestData
     *
     * @param array $priceData
     */
    public function testCreatePayloadFromEntity(array $priceData): void
    {
        $price = $this->builder()->build(Price::class, $priceData);

        $pricePayload = $this->serializer()->serialize($price);

        $this->assertCount(count($priceData), $pricePayload);
        $this->assertSame($priceData, $pricePayload);

        $this->assertCount(count($priceData), $pricePayload);
        $this->assertSame($priceData, $pricePayload);

        $this->assertArraySimilar($priceData, $pricePayload);
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

    public function defaultTestData(): Generator
    {
        yield 'test_01' => [
            ProductSampleData::price(),
        ];
    }
}
