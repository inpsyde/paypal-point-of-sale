<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
// phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata\Metadata;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata\Source;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\Product;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\StockQuantityAwareInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\Variant;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionDefinitions;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter\DescriptionLengthFilter;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Serializer\CallbackSerializer;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
$key = static function (string $className): string {
    return "paypal-pos.sdk.serializer.{$className}";
};
$serializer = static function (callable $callable) {
    return static function (ContainerInterface $container) use ($callable): SerializerInterface {
        return new CallbackSerializer($callable);
    };
};
return [$key(ProductInterface::class) => $serializer(static function (ProductInterface $product, SerializerInterface $serializer) {
    $data = ['uuid' => $product->uuid(), 'name' => $product->name(), 'description' => DescriptionLengthFilter::limitDescription($product->description()), 'imageLookupKeys' => $serializer->serialize($product->images()), 'variants' => $serializer->serialize($product->variants())];
    if ($product->unitName()) {
        $data['unitName'] = $product->unitName();
    }
    if ($product->presentation()) {
        $data['presentation'] = $serializer->serialize($product->presentation());
    }
    if ($product->externalReference()) {
        $data['externalReference'] = $product->externalReference();
    }
    if ($product->etag()) {
        $data['etag'] = $product->etag();
    }
    if ($product->updatedAt()) {
        $data['updatedAt'] = $product->updatedAt()->format(\DateTime::ATOM);
    }
    if ($product->updatedBy()) {
        $data['updatedBy'] = $product->updatedBy();
    }
    if ($product->createdAt()) {
        $data['createdAt'] = $product->createdAt()->format(\DateTime::ATOM);
    }
    if ($product->vat()) {
        $data['vatPercentage'] = $product->vat()->percentage();
    }
    if ($product->taxExempt() !== null) {
        $data['taxExempt'] = $product->taxExempt();
    }
    if ($product->usesDefaultTax() !== null) {
        $data['createWithDefaultTax'] = $product->usesDefaultTax();
    }
    if ($product->metadata()) {
        $data['metadata'] = $serializer->serialize($product->metadata());
    }
    if ($product->variantOptionDefinitions()) {
        $data['variantOptionDefinitions'] = $serializer->serialize($product->variantOptionDefinitions());
    }
    return $data;
}), $key(ProductCollection::class) => $serializer(static function (ProductCollection $productCollection, SerializerInterface $serializer) {
    $data = [];
    /** @var Product $product */
    foreach ($productCollection->all() as $product) {
        $data[] = $serializer->serialize($product);
    }
    return $data;
}), $key(VariantInterface::class) => $serializer(static function (VariantInterface&StockQuantityAwareInterface $variant, SerializerInterface $serializer) {
    $data = ['uuid' => $variant->uuid(), 'name' => $variant->name(), 'sku' => $variant->sku(), 'defaultQuantity' => $variant->defaultQuantity()];
    if ($variant->unitName()) {
        $data['unitName'] = $variant->unitName();
    }
    if ($variant->price()) {
        $data['price'] = $serializer->serialize($variant->price());
    }
    $variantOptions = $variant->options();
    if ($variantOptions !== null && $variantOptions->all()) {
        $data['options'] = $serializer->serialize($variantOptions);
    }
    if ($variant->presentation()) {
        $data['presentation'] = $serializer->serialize($variant->presentation());
    }
    if ($variant->barcode() !== null) {
        $data['barcode'] = $variant->barcode();
    }
    if ($variant->costPrice()) {
        $data['costPrice'] = $serializer->serialize($variant->costPrice());
    }
    if ($variant->vat()) {
        $data['vatPercentage'] = $variant->vat()->percentage();
    }
    return $data;
}), $key(VariantCollection::class) => $serializer(static function (VariantCollection $variantCollection, SerializerInterface $serializer) {
    $data = [];
    /** @var Variant $variant */
    foreach ($variantCollection->all() as $variant) {
        $data[] = $serializer->serialize($variant);
    }
    return $data;
}), $key(VariantOptionCollection::class) => $serializer(static function (VariantOptionCollection $variantOptionCollection, SerializerInterface $serializer) {
    $data = [];
    foreach ($variantOptionCollection->all() as $variantOption) {
        $data[] = $serializer->serialize($variantOption);
    }
    return $data;
}), $key(VariantOption::class) => $serializer(static function (VariantOption $variantOption, SerializerInterface $serializer) {
    return ['name' => $variantOption->name(), 'value' => $variantOption->value()];
}), $key(VariantOptionDefinitions::class) => $serializer(static function (VariantOptionDefinitions $optionDefinitions, SerializerInterface $serializer) {
    $definitions = [];
    foreach ($optionDefinitions->definitions() as $name => $properties) {
        $props = [];
        foreach ($properties->all() as $item) {
            $image = $item->image();
            $imageUrl = $image ? $image->largeImageUrl() : null;
            $props[] = ['value' => $item->value(), 'imageUrl' => $imageUrl];
        }
        $definitions[] = ['name' => $name, 'properties' => $props];
    }
    return ['definitions' => $definitions];
}), $key(ImageInterface::class) => $serializer(static function (ImageInterface $image, SerializerInterface $serializer) {
    return $image->imageLookupKey();
}), $key(ImageCollection::class) => $serializer(static function (ImageCollection $imageCollection, SerializerInterface $serializer) {
    $data = [];
    foreach ($imageCollection->all() as $image) {
        $data[] = $image->imageLookupKey();
    }
    return $data;
}), $key(Presentation::class) => $serializer(static function (Presentation $presentation, SerializerInterface $serializer) {
    return ['imageUrl' => $presentation->image()->largeImageUrl(), 'backgroundColor' => null, 'textColor' => null];
}), $key(Price::class) => $serializer(static function (Price $price, SerializerInterface $serializer) {
    return ['amount' => $price->amount(), 'currencyId' => $price->currencyId()];
}), $key(Vat::class) => $serializer(static function (Vat $vat, SerializerInterface $serializer) {
    return ['vatPercentage' => $vat->percentage()];
}), $key(Metadata::class) => $serializer(static function (Metadata $metadata, SerializerInterface $serializer) {
    return ['inPos' => $metadata->isInPos(), 'source' => $serializer->serialize($metadata->source())];
}), $key(Source::class) => $serializer(static function (Source $source, SerializerInterface $serializer) {
    return ['name' => $source->name(), 'external' => $source->isExternal()];
})];
