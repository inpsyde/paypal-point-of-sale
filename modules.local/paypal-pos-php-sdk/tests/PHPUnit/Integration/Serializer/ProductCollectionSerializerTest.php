<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductCollection;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class ProductCollectionSerializerTest extends ZettlePhpSdkStandaloneTestCase
{

    use AssertArraySimilarTrait;
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
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::products()
     *
     * @param array $sampleProductData
     *
     * @throws Exception
     */
    public function testCreatePayloadFromCollection(array $sampleProductData): void
    {
        $productCollection = $this->builder()->build(ProductCollection::class, $sampleProductData);
        $productCollectionPayload = $this->serializer()->serialize($productCollection);

        $this->assertIsArray($productCollectionPayload);
        $this->assertNotEmpty($productCollectionPayload);
        $this->assertCount(count($sampleProductData), $productCollectionPayload);
        /**
         * We currently don't serialize these because the's override the product image in the iZ android app
         */
        foreach ($sampleProductData as &$productPayload) {
            $productPayload['presentation']['backgroundColor'] = null;
            $productPayload['presentation']['textColor'] = null;
        }

        $this->assertEqualsCanonicalizing($sampleProductData, $productCollectionPayload);

        foreach ($productCollectionPayload as $productKey => $productPayload) {
            $this->assertIsArray($productPayload);
            $this->assertNotEmpty($productPayload);
            $this->assertCount(count($sampleProductData[$productKey]), $productPayload);
        }
    }

    /** @return BuilderInterface */
    public function builder(): BuilderInterface
    {
        return $this->get('paypal-pos.sdk.builder');
    }

    /** @return SerializerInterface */
    public function serializer(): SerializerInterface
    {
        return $this->get('paypal-pos.sdk.serializer');
    }
}
