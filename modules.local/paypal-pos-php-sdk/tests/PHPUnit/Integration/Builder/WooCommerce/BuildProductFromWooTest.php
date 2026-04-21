<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Image\Images;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationMode;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\PlaceholderUrlProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\UrlProviderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Vat\VatProvider;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ArrayProductRepository;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Stub\ArrayContainer;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\ValidatorInterface;
use Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository\BarcodeRetrieverInterface;
use Mockery\LegacyMockInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;
use function Brain\Monkey\Functions\expect;

class BuildProductFromWooTest extends ZettlePhpSdkStandaloneTestCase
{

    use SetUpIdMapTrait;

    private $wcConfig;

    protected function setUp(): void
    {
        $this->delayModuleContainerSetup();
        $this->setUpIdMap(ConnectionType::PRODUCT);
        $this->setUpIdMap(ConnectionType::VARIANT);
        $this->setUpIdMap(ConnectionType::IMAGE);

        $this->injectFactory(
            'paypal-pos.sdk.dal.provider.vat.wc',
            function (ContainerInterface $container): VatProvider
            {
                $mock=Mockery::mock(VatProvider::class);
                $mock->shouldReceive('provide')->andReturn(new Vat(20.0));
                return $mock;
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.dal.provider.image.url',
            function (ContainerInterface $container): UrlProviderInterface {
                return new PlaceholderUrlProvider('foo');
            }
        );
        /**
         * LazyImageValidator performs checks on an image that cannot exist. Mock it away
         */
        $this->injectFactory(
            'paypal-pos.sdk.validator.image',
            function (ContainerInterface $container): ValidatorInterface {
                $mock = Mockery::mock(ValidatorInterface::class);
                $mock->shouldReceive('accepts')->andReturn(false);
                $mock->shouldNotReceive('validate');

                return $mock;
            }
        );
        $this->injectFactory(
            'paypal-pos.sdk.api.images',
            function (ContainerInterface $container): Images {
                $mock = Mockery::mock(Images::class);

                return $mock;
            }
        );
        $this->injectFactory(
            'paypal-pos.sdk.api.products',
            function (ContainerInterface $container): Products {
                $mock = Mockery::mock(Products::class);
                $mock->shouldReceive('read')->andThrow(new ZettleRestException('cannot read'));
                $mock
                    ->shouldReceive('create')
                    ->andReturnUsing(function (): ProductInterface {
                        $productMock = Mockery::mock(ProductInterface::class);
                        $productMock->shouldReceive('uuid')->andReturn(Uuid::v1());

                        return $productMock;
                    });

                return $mock;
            }
        );

        $this->wcConfig = Mockery::mock(ContainerInterface::class);
        $this->wcConfig
            ->shouldReceive('get')
            ->andReturn('');

        $this->injectFactory(
            'paypal-pos.sdk.config.woocommerce-config',
            function () {
                return $this->wcConfig;
            }
        );

        $optionsContainer = new ArrayContainer([]);
        $this->injectFactory(
            'paypal-pos.settings',
            function () use ($optionsContainer) {
                return $optionsContainer;
            }
        );

        $this->injectFactory(
            'paypal-pos.sync.price-sync-enabled',
            function (): bool {
                return false;
            }
        );

        $this->injectFactory(
            'paypal-pos.sync.taxation-mode',
            function (): string {
                return TaxationMode::INCLUSIVE;
            }
        );

        $this->injectFactory(
            'paypal-pos.sync.taxation-type',
            function (): string {
                return TaxationType::VAT;
            }
        );

        $this->injectFactory(
            'paypal-pos.product-settings.barcode.repository',
            function (ContainerInterface $container): BarcodeRetrieverInterface
            {
                $mock = Mockery::mock(BarcodeRetrieverInterface::class);
                $mock->shouldReceive('get')->andReturnNull();
                return $mock;
            }
        );

        $this->injectFactory(
            'paypal-pos.logger.woocommerce',
            function () {
                return new NullLogger();
            }
        );

        parent::setUp();
    }

    public function testBuildSimpleProduct()
    {
        $this->setupModuleContainer();
        expect('home_url')->andReturn('http://foo.bar');
        expect('get_option')->with('woocommerce_weight_unit')->andReturn('hurr');
        expect('sanitize_title')->andReturnUsing([$this, 'sanitizeTitle']);
        $wcProduct = Mockery::mock(WC_Product_Simple::class.', '.WC_Product::class);
        $wcProduct->shouldReceive('get_id')->andReturn(42);
        $this->mockType($wcProduct, 'simple');
        $wcProduct->shouldReceive('get_weight')->andReturn('hurr');
        $wcProduct->shouldReceive('get_name')->andReturn('Lorem');
        $wcProduct->shouldReceive('get_sku')->andReturn(uniqid('sku_'));
        $wcProduct->shouldReceive('wc_get_price_including_tax')->andReturn(12);
        $wcProduct->shouldReceive('get_stock_quantity')->andReturn(12);
        $wcProduct->shouldReceive('managing_stock')->andReturn(true);
        $wcProduct->shouldReceive('is_purchasable')->andReturn(true);
        $wcProduct->shouldReceive('get_description')->andReturn('Ipsum');
        $wcProduct->shouldReceive('get_image_id')->andReturn('1');
        $wcProduct->shouldReceive('get_children')->andReturn([]);
        $wcProduct->shouldReceive('get_gallery_image_ids')->andReturn([2, 3, 4]);
        $wcProduct->shouldReceive('get_date_created')->andReturn(new DateTime());
        $wcProduct->shouldReceive('get_tax_class')->andReturn('');
        $wcProduct->shouldReceive('get_tax_status')->andReturn('taxable');
        $wcProduct->shouldReceive('get_attributes')->andReturn(
            [
                $this->mockAttribute(),
            ]
        );
        expect('wc_get_price_including_tax')->with($wcProduct)->once()->andReturn(22);

        $product = $this->builder()->build(ProductInterface::class, $wcProduct);

        $this->assertInstanceOf(ProductInterface::class, $product);
    }

    public function sanitizeTitle($str)
    {
        return trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower($str)), '-');
    }

    public function testBuildVariableProduct()
    {
        expect('home_url')->andReturn('http://foo.bar');
        expect('get_option')->with('woocommerce_weight_unit')->andReturn('hurr');
        expect('sanitize_title')->andReturnUsing([$this, 'sanitizeTitle']);

        $wcProduct = Mockery::mock(WC_Product_Variable::class);
        $wcProduct->shouldReceive('get_id')->andReturn(42);
        $this->mockType($wcProduct, 'variable');
        $wcProduct->shouldReceive('get_weight')->andReturn('hurr');
        $wcProduct->shouldReceive('get_name')->andReturn('Lorem');
        $wcProduct->shouldReceive('get_sku')->andReturn(uniqid('sku_'));
        $wcProduct->shouldReceive('get_stock_quantity')->andReturn(13);
        $wcProduct->shouldReceive('managing_stock')->andReturn(true);
        $wcProduct->shouldReceive('is_purchasable')->andReturn(true);
        $wcProduct->shouldReceive('get_description')->andReturn('Ipsum');
        $wcProduct->shouldReceive('get_image_id')->andReturn('1');
        $wcProduct->shouldReceive('get_gallery_image_ids')->andReturn([2, 3, 4]);
        $wcProduct->shouldReceive('get_date_created')->andReturn(new DateTime());
        $wcProduct->shouldReceive('get_tax_class')->andReturn('');
        $wcProduct->shouldReceive('get_tax_status')->andReturn('taxable');


        $colorAttrName = 'color of item';
        $colorAttrOptions = ['Lime Green', 'Midnight Blue', 'Deep Sky Blue'];
        $unusedAttrName = 'bar';
        $unusedAttrOptions = ['b', 'bb'];

        $variationAttributes = [
            $colorAttrName => $colorAttrOptions,
            $unusedAttrName => $unusedAttrOptions,
        ];

        $wcProduct->shouldReceive('get_variation_attributes')->andReturn($variationAttributes);
        $wcProduct->shouldReceive('get_attributes')->andReturn(
            [
                $colorAttrName => $this->mockAttribute($colorAttrName, $colorAttrOptions),
                $unusedAttrName => $this->mockAttribute($unusedAttrName, $unusedAttrOptions),
            ]
        );
        $wcProduct->shouldReceive('get_visible_children')->andReturn([1, 2, 3]);
        $wcProduct->shouldReceive('get_children')->andReturn([1, 2, 3]);

        $variations = [
            1 => $this->mockVariation(42, 1, [$colorAttrName => $colorAttrOptions[0]]),
            2 => $this->mockVariation(42, 2, [$colorAttrName => $colorAttrOptions[1]]),
            3 => $this->mockVariation(42, 3, [$colorAttrName => $colorAttrOptions[2]]),
        ];

        $this->injectFactory(
            'paypal-pos.sdk.repository.woocommerce.product',
            function (ContainerInterface $container) use ($wcProduct, $variations): ProductRepositoryInterface {
                return new ArrayProductRepository(
                    [
                        42 => $wcProduct,
                    ] + $variations
                );
            }
        );

        expect('wc_get_price_including_tax')->with($wcProduct)->times(count($variations))->andReturn(22);

        $this->setupModuleContainer();

        $product = $this->builder()->build(ProductInterface::class, $wcProduct);

        $this->assertInstanceOf(ProductInterface::class, $product);

        $definitions = $product->variantOptionDefinitions()->definitions();
        $expectedDefinitions = [
            $colorAttrName => new VariantOptionCollection(
                ...array_map(function (string $option) use ($colorAttrName): VariantOption {
                    return new VariantOption($colorAttrName, $option);
                }, $colorAttrOptions)
            ),
        ];
        $this->assertEquals($expectedDefinitions, $definitions);

        $variants = array_values($product->variants()->all());
        foreach ($variants as $i => $variant) {
            assert($variant instanceof VariantInterface);

            $this->assertEquals([
                new VariantOption($colorAttrName, $colorAttrOptions[$i]),
            ], $variant->options()->all());
        }
    }

    /** @return BuilderInterface */
    public function builder(): BuilderInterface
    {
        return $this->get('paypal-pos.sdk.builder');
    }

    private function mockVariation(
        int $parentId,
        int $variationId,
        array $attributes = []
    ): WC_Product_Variation {
        $variation = Mockery::mock(WC_Product_Variation::class);

        $variation->shouldReceive('get_id')->andReturn($variationId);
        $this->mockType($variation, 'variation');
        $variation->shouldReceive('get_parent_id')->andReturn($parentId);
        $variation->shouldReceive('get_name')->andReturn(uniqid('Variation_'));
        $variation->shouldReceive('get_description')->andReturn(uniqid('Description_'));
        $variation->shouldReceive('get_sku')->andReturn(uniqid('sku_'));
        $variation->shouldReceive('get_stock_quantity')->andReturn(12);
        $variation->shouldReceive('managing_stock')->andReturn(true);
        $variation->shouldReceive('is_purchasable')->andReturn(true);
        $variation->shouldReceive('get_visible_children')->andReturn([]);
        $variation->shouldReceive('get_children')->andReturn([]);
        $variation->shouldReceive('get_attributes')->andReturn($attributes);
        $variation->shouldReceive('get_image_id')->andReturn(2);
        $variation->shouldReceive('get_weight')->andReturn('hurr');
        $variation->shouldReceive('get_date_created')->andReturn(new DateTime());
        $variation->shouldReceive('get_tax_class')->andReturn('');
        expect('wc_get_product')->with($variationId)->andReturn($variation);
        foreach ($attributes as $name => $option) {
            expect('wc_attribute_label')->with($name, $variation)->andReturn($name);
        }

        assert($variation instanceof WC_Product_Variation);

        return $variation;
    }

    private function mockAttribute(string $name = null, ?array $options = null): WC_Product_Attribute
    {
        $name = $name ?? uniqid('attribute_');

        $options = $options ?? [
                'foo',
                'bar',
                'baz',
            ];

        $attribute = Mockery::mock(WC_Product_Attribute::class);

        $attribute->shouldReceive('get_name')->andReturn($name);
        $attribute->shouldReceive('get_options')->andReturn($options);
        $attribute->shouldReceive('is_taxonomy')->andReturn(false);

        return $attribute;
    }

    private function mockType(LegacyMockInterface $mock, string $returnedType): void
    {
        $mock->shouldReceive('get_type')->andReturn($returnedType);

        $mock
            ->shouldReceive('is_type')
            ->andReturnUsing(function (string $type) use ($returnedType): bool {
                return $type === $returnedType;
            });
    }
}
