<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;
use Symfony\Component\Uid\Uuid;

class BuildProductTest extends ZettlePhpSdkStandaloneTestCase
{
    use SetUpIdMapTrait;

    public function defaultTestData()
    {
        yield 'first' => [
            ProductSampleData::sampleProductData(),
        ];
    }

    protected function setUp(): void
    {
        $data = $this->getProvidedData();

        $product = $data[0];
        $isSynced = $data[1] ?? false;

        $idMap = [];

        if ($isSynced) {
            $idMap[rand(1, 999)] = $product['uuid'];
        }

        $this->setUpIdMap(ConnectionType::PRODUCT, $idMap);
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
     * @dataProvider defaultTestData
     *
     * @param array $productSampleData
     */
    public function testCreateEntityFromPayload(array $productSampleData): void
    {
        $product = $this->builder()->build(ProductInterface::class, $productSampleData);

        $this->assertTrue(Uuid::isValid($product->uuid()));
        $this->assertSame($productSampleData['name'], $product->name());
        $this->assertSame($productSampleData['description'], $product->description());

        $this->assertIsArray($product->images()->all());
        $this->assertNotEmpty($product->images()->all());

        $this->assertSame(
            $productSampleData['presentation']['imageUrl'],
            $product->presentation()->image()->largeImageUrl()
        );
        $this->assertSame(
            $productSampleData['presentation']['backgroundColor'],
            $product->presentation()->backgroundColor()
        );
        $this->assertSame(
            $productSampleData['presentation']['textColor'],
            $product->presentation()->textColor()
        );

        $this->assertIsArray($product->variants()->all());
        $this->assertNotEmpty($product->variants()->all());
    }

    /**
     * @dataProvider \Syde\PayPal\PointOfSale\PhpSdk\Tests\DataProvider\ProductSampleData::product()
     *
     * @param array $productSampleData
     */
    public function testCreatePayloadFromEntity(array $productSampleData): void
    {
        $product = $this->builder()->build(ProductInterface::class, $productSampleData);

        $productPayload = $this->serializer()->serialize($product);

        $this->assertIsArray($productPayload);
        $this->assertNotEmpty($productPayload);
        $this->assertCount(count($productSampleData), $productPayload);
        /**
         * We currently don't serialize these because the's override the product image in the iZ android app
         */
        $productSampleData['presentation']['backgroundColor'] = null;
        $productSampleData['presentation']['textColor'] = null;
        $this->assertEqualsCanonicalizing($productSampleData, $productPayload);
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
