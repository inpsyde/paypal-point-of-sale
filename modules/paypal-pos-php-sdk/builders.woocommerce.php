<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle;

// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
use Syde\Vendor\Zettle\Inpsyde\WcProductContracts\ProductType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\AttributeSetBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface as B;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\CallbackBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\PriceBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\VariantBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\VariantOptionCollectionBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\VariantOptionDefinitionsBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Repository\Variant\VariantBuilderRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ConcreteImage;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\LazyImage;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata\Metadata;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata\Source;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\Product;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\WcVariationIterator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\AttributeSet;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionDefinitions;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\UrlProviderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Vat\VatProvider;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Iterator\WcProductAttachmentIterator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Uuid\Uuid;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
$key = static function (string $className): string {
    return "paypal-pos.sdk.builder.woocommerce.{$className}";
};
$builder = static function (callable $callable) {
    return static function (C $container) use ($callable): B {
        return new CallbackBuilder(static function (string $className, $payload, B $builder) use ($callable, $container) {
            return $callable($payload, $builder, $container);
        });
    };
};
return [$key(ProductInterface::class) => $builder(static function (\WC_Product $wcProduct, B $builder, C $container) {
    $presentation = null;
    $imageId = (int) $wcProduct->get_image_id();
    if ($imageId) {
        try {
            $presentation = $builder->build(Presentation::class, $wcProduct);
        } catch (BuilderException $exception) {
            $container->get('inpsyde.debug.exception-handler')->handle($exception);
        }
    }
    $taxationType = $container->get('paypal-pos.sync.taxation-type');
    $priceSyncEnabled = $container->get('paypal-pos.sync.price-sync-enabled');
    $taxStatus = $wcProduct->get_tax_status();
    // IZET-374, can send taxExempt=true only for sales tax, for others should be false
    $taxExempt = $taxStatus !== 'taxable' && $taxationType === TaxationType::SALES_TAX;
    $useDefaultTax = $taxationType === TaxationType::SALES_TAX || $taxationType === TaxationType::VAT && !$priceSyncEnabled;
    $vat = $taxationType === TaxationType::VAT && !$useDefaultTax ? $builder->build(Vat::class, $wcProduct) : null;
    $product = new Product((string) Uuid::fromWcProduct($wcProduct), $wcProduct->get_name(), $wcProduct->get_description(), $builder->build(ImageCollection::class, $wcProduct), $builder->build(VariantCollection::class, $wcProduct), $presentation, null, null, null, null, new \DateTime('now'), $vat, $taxExempt, $useDefaultTax, null, new Metadata(\true, $builder->build(Source::class, $wcProduct)));
    if ($wcProduct instanceof \WC_Product_Variable && !empty($wcProduct->get_visible_children())) {
        $product->setVariantOptionDefinitions($builder->build(VariantOptionDefinitions::class, $wcProduct));
    }
    return $product;
}), $key(VariantInterface::class) => static function (C $container) {
    return new VariantBuilder(static function (\Throwable $exception) use ($container): void {
        $container->get('inpsyde.debug.exception-handler')->handle($exception);
    }, $container->get('paypal-pos.sync.taxation-type'), $container->get('paypal-pos.sync.price-sync-enabled'), $container->get('paypal-pos.product-settings.barcode.repository'));
}, $key(VariantCollection::class) => $builder(static function (\WC_Product $wcProduct, B $builder, C $container) {
    $collection = new VariantCollection();
    $variantBuilderRepository = $container->get('paypal-pos.sdk.builder.repository.variant');
    \assert($variantBuilderRepository instanceof VariantBuilderRepositoryInterface);
    switch (\true) {
        case $wcProduct->is_type(ProductType::VARIABLE):
            \assert($wcProduct instanceof \WC_Product_Variable);
            // Case: Variable Products without Variations
            if (empty($wcProduct->get_visible_children())) {
                return $collection;
            }
            $variationIterator = new WcVariationIterator($wcProduct, $builder, $container->get('paypal-pos.sdk.repository.woocommerce.product'));
            foreach ($variationIterator as $variationId => $variant) {
                if (!$variant instanceof VariantInterface) {
                    continue;
                }
                $collection->add($variant);
            }
            return $collection;
        case $wcProduct->is_type(ProductType::SIMPLE):
            return $variantBuilderRepository->addToCollection($wcProduct, $collection);
    }
    return $collection;
}), $key(VariantOptionCollection::class) => static function (C $container): B {
    return new VariantOptionCollectionBuilder();
}, $key(VariantOptionDefinitions::class) => static function (C $container): B {
    return new VariantOptionDefinitionsBuilder();
}, $key(AttributeSet::class) => static function (C $container): B {
    return new AttributeSetBuilder($container->get('paypal-pos.sdk.repository.woocommerce.product'));
}, $key(ImageInterface::class) => $builder(static function (\WC_Product $wcProduct, B $builder, C $container) {
    $imageIdMap = $container->get('paypal-pos.sdk.id-map.image');
    \assert($imageIdMap instanceof OneToOneMapInterface);
    $imageId = (int) $wcProduct->get_image_id();
    try {
        return new ConcreteImage($imageIdMap->remoteId($imageId));
    } catch (IdNotFoundException $exception) {
        $urlProvider = $container->get('paypal-pos.sdk.dal.provider.image.url');
        \assert($urlProvider instanceof UrlProviderInterface);
        return new LazyImage($imageId, $urlProvider, $container->get('paypal-pos.sdk.api.images'), $imageIdMap);
    }
}), $key(ImageCollection::class) => $builder(static function (\WC_Product $wcProduct, B $builder, C $container) {
    $imageIdMap = $container->get('paypal-pos.sdk.id-map.image');
    $urlProvider = $container->get('paypal-pos.sdk.dal.provider.image.url');
    $imageClient = $container->get('paypal-pos.sdk.api.images');
    \assert($imageIdMap instanceof OneToOneMapInterface);
    \assert($urlProvider instanceof UrlProviderInterface);
    $imageIds = new WcProductAttachmentIterator($wcProduct, 10);
    $images = [];
    foreach ($imageIds as $imageId) {
        try {
            $images[] = new ConcreteImage($imageIdMap->remoteId($imageId));
        } catch (IdNotFoundException $exception) {
            $images[] = new LazyImage($imageId, $urlProvider, $imageClient, $imageIdMap);
        }
    }
    return new ImageCollection(...$images);
}), $key(Presentation::class) => $builder(static function (\WC_Product $wcProduct, B $builder, C $container) {
    return new Presentation($builder->build(ImageInterface::class, $wcProduct));
}), $key(Price::class) => static function (C $container): B {
    return new PriceBuilder($container->get('paypal-pos.sdk.config.woocommerce-config'), $container->get('paypal-pos.sync.taxation-mode'));
}, $key(Vat::class) => $builder(static function (\WC_Product $wcProduct, B $builder, C $container) {
    $vatProvider = $container->get('paypal-pos.sdk.dal.provider.vat.wc');
    \assert($vatProvider instanceof VatProvider);
    return $vatProvider->provide($wcProduct);
}), $key(Source::class) => $builder(static function (\WC_Product $product, B $builder, C $container) {
    return new Source('WooCommerce', \true);
})];
