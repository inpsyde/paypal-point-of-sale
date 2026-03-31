<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle;

// phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface as B;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\CallbackBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\ImageBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Inventory\Inventory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Location;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Type\LocationType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata\Metadata;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata\Source;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\Organization;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationMode;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\Product;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Tax\TaxRate;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\Variant;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantInventoryState\VariantInventoryState;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantInventoryState\VariantInventoryStateCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
$key = static function (string $className): string {
    return "paypal-pos.sdk.builder.array.{$className}";
};
$builder = static function (callable $callable) {
    return static function (C $container) use ($callable): B {
        return new CallbackBuilder(static function (string $className, $payload, B $builder) use ($callable, $container) {
            return $callable($payload, $builder, $container);
        });
    };
};
return [
    $key(Presentation::class) => $builder(static function (array $payload, B $builder): Presentation {
        $backgroundColor = isset($payload['backgroundColor']) ? $payload['backgroundColor'] : null;
        $textColor = isset($payload['textColor']) ? $payload['textColor'] : null;
        return new Presentation($builder->build(ImageInterface::class, $payload), $backgroundColor, $textColor);
    }),
    //<editor-fold desc="Images">
    $key(ImageInterface::class) => static function (C $container): B {
        return new ImageBuilder();
    },
    $key(ImageCollection::class) => $builder(static function (array $payload, B $builder): ImageCollection {
        // phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
        $images = \array_map(static function ($imagePayload) use ($builder) {
            return $builder->build(ImageInterface::class, (array) $imagePayload);
        }, $payload);
        // phpcs:enable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
        return new ImageCollection(...$images);
    }),
    //</editor-fold>
    //<editor-fold desc="Variants">
    $key(VariantInterface::class) => $builder(static function (array $payload, B $builder): Variant {
        $presentation = isset($payload['presentation']) ? $builder->build(Presentation::class, $payload['presentation']) : null;
        $options = isset($payload['options']) ? $builder->build(VariantOptionCollection::class, $payload['options']) : new VariantOptionCollection();
        $vatPercentage = isset($payload['vatPercentage']) ? new Vat((float) $payload['vatPercentage']) : null;
        $costPrice = isset($payload['costPrice']) ? $builder->build(Price::class, $payload['costPrice']) : null;
        $defaultQuantity = isset($payload['defaultQuantity']) ? (int) $payload['defaultQuantity'] : 0;
        $unitName = isset($payload['unitName']) ? $payload['unitName'] : null;
        $barcode = isset($payload['barcode']) ? $payload['barcode'] : null;
        return new Variant($payload['uuid'], $payload['name'] ?? '', $payload['description'] ?? '', $payload['sku'] ?? '', $defaultQuantity, $builder->build(Price::class, $payload['price'] ?? []), $vatPercentage, $presentation, $options, $unitName, $costPrice, $barcode);
    }),
    $key(VariantCollection::class) => $builder(static function (array $payload, B $builder): VariantCollection {
        $variants = \array_map(static function (array $state) use ($builder) {
            return $builder->build(VariantInterface::class, $state);
        }, $payload);
        return new VariantCollection(...$variants);
    }),
    $key(VariantOption::class) => $builder(static function (array $payload, B $builder): VariantOption {
        return new VariantOption($payload['name'], $payload['value']);
    }),
    $key(VariantOptionCollection::class) => $builder(static function (array $payload, B $builder): VariantOptionCollection {
        $variants = \array_map(static function (array $state) use ($builder) {
            return $builder->build(VariantOption::class, $state);
        }, $payload);
        return new VariantOptionCollection(...$variants);
    }),
    //</editor-fold>
    //<editor-fold desc="Products">
    $key(ProductInterface::class) => $builder(static function (array $payload, B $builder): ProductInterface {
        $presentation = isset($payload['presentation']) ? $builder->build(Presentation::class, $payload['presentation']) : null;
        $updated = isset($payload['updated']) ? new \DateTime($payload['updated']) : null;
        $updatedBy = isset($payload['updatedBy']) ? $payload['updatedBy'] : null;
        $created = isset($payload['created']) ? new \DateTime($payload['created']) : null;
        $vatPercentage = isset($payload['vatPercentage']) ? new Vat((float) $payload['vatPercentage']) : null;
        $taxExempt = isset($payload['taxExempt']) ? (bool) $payload['taxExempt'] : null;
        $externalReference = isset($payload['externalReference']) ? $payload['externalReference'] : null;
        $etag = isset($payload['etag']) ? $payload['etag'] : null;
        $unitName = isset($payload['unitName']) ? $payload['unitName'] : null;
        $metadata = isset($payload['metadata']) ? $builder->build(Metadata::class, $payload['metadata']) : null;
        return new Product($payload['uuid'], $payload['name'], $payload['description'] ?? '', $builder->build(ImageCollection::class, $payload['imageLookupKeys']), $builder->build(VariantCollection::class, $payload['variants']), $presentation, $externalReference, $etag, $updated, $updatedBy, $created, $vatPercentage, $taxExempt, null, $unitName, $metadata);
    }),
    $key(ProductCollection::class) => $builder(static function (array $payload, B $builder): ProductCollection {
        $products = \array_map(static function (array $state) use ($builder) {
            return $builder->build(ProductInterface::class, $state);
        }, $payload);
        return new ProductCollection(...$products);
    }),
    //</editor-fold>
    //<editor-fold desc="Inventory">
    $key(VariantInventoryState::class) => $builder(static function (array $payload, B $builder): VariantInventoryState {
        return new VariantInventoryState($payload['inventoryUuid'], $payload['productUuid'], $payload['variantUuid'], (int) $payload['balance']);
    }),
    $key(VariantInventoryStateCollection::class) => $builder(static function (array $payload, B $builder): VariantInventoryStateCollection {
        $states = \array_map(static function (array $state) use ($builder) {
            return $builder->build(VariantInventoryState::class, $state);
        }, $payload);
        return new VariantInventoryStateCollection($states);
    }),
    $key(Inventory::class) => $builder(static function (array $payload, B $builder): Inventory {
        $variants = $builder->build(VariantInventoryStateCollection::class, $payload);
        return new Inventory($variants);
    }),
    $key(Location::class) => $builder(static function (array $payload, B $builder): Location {
        return new Location($payload['inventoryUuid'], LocationType::get($payload['inventoryType']), $payload['name'], $payload['description'] ?? null, isset($payload['defaultInventory']) && (bool) $payload['defaultInventory']);
    }),
    $key(TaxRate::class) => $builder(static function (array $payload, B $builder): TaxRate {
        $percentage = $payload['percentage'] ? (float) $payload['percentage'] : null;
        $default = isset($payload['default']) ? (bool) $payload['default'] : \false;
        return new TaxRate($payload['uuid'], $payload['label'], $percentage, $default);
    }),
    //</editor-fold>
    $key(Price::class) => $builder(static function (array $payload, B $builder, C $container): Price {
        $wooConfig = $container->get('paypal-pos.sdk.config.woocommerce-config');
        $amount = isset($payload['amount']) ? (int) $payload['amount'] : 0;
        $currency = isset($payload['currencyId']) ? $payload['currencyId'] : $wooConfig->get('currency');
        return new Price($amount, $currency);
    }),
    $key(Vat::class) => $builder(static function (array $payload, B $builder): Vat {
        return new Vat((float) \current($payload));
    }),
    $key(Metadata::class) => $builder(static function (array $payload, B $builder): Metadata {
        return new Metadata($payload['inPos'], $builder->build(Source::class, $payload['source']));
    }),
    $key(Source::class) => $builder(static function (array $payload, B $builder): Source {
        return new Source($payload['name'], $payload['external']);
    }),
    $key(Organization::class) => $builder(static function (array $payload, B $builder, C $container): Organization {
        $created = isset($payload['created']) ? new \DateTime($payload['created']) : null;
        $organizationId = isset($payload['organizationId']) ? (int) $payload['organizationId'] : null;
        $taxationMode = isset($payload['taxationMode']) ? (string) $payload['taxationMode'] : TaxationMode::INCLUSIVE;
        $taxationType = isset($payload['taxationType']) ? (string) $payload['taxationType'] : TaxationType::VAT;
        $timezone = isset($payload['timeZone']) ? new \DateTimeZone($payload['timeZone']) : null;
        return new Organization($payload['uuid'], $taxationType === TaxationType::VAT ? new Vat($payload['vatPercentage']) : null, $payload['currency'], $payload['name'] ?? null, $payload['city'] ?? null, $payload['zipCode'] ?? null, $payload['address'] ?? null, $payload['phoneNumber'] ?? null, $payload['contactEmail'] ?? null, $payload['receiptEmail'] ?? null, $payload['legalEntityType'] ?? null, $payload['legalEntityNr'] ?? null, $payload['country'] ?? null, $payload['language'] ?? null, $created, $payload['ownerUuid'] ?? null, $organizationId, $payload['customerStatus'] ?? null, $taxationMode, $taxationType, $payload['customerType'] ?? null, $timezone, $payload['addressLine2'] ?? null, $payload['legalName'] ?? null, $payload['legalZipCode'] ?? null, $payload['legalCity'] ?? null, $payload['legalState'] ?? null);
    }),
];
