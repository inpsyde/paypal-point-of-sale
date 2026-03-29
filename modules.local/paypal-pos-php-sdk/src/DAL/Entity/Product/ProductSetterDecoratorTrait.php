<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product;

use DateTime;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata\Metadata;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionDefinitions;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;

/**
 * Trait ProductSetterDecoratorTrait
 * phpcs:disable Inpsyde.CodeQuality.NoAccessors
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product
 */
trait ProductSetterDecoratorTrait
{
    abstract protected function baseWritableProduct(): WritableProductInterface;

    public function setUuid(string $uuid): void
    {
        $this->baseWritableProduct()->setUuid($uuid);
    }

    public function setName(string $name): void
    {
        $this->baseWritableProduct()->setName($name);
    }

    public function setDescription(string $description): void
    {
        $this->baseWritableProduct()->setDescription($description);
    }

    public function setImages(ImageCollection $imageCollection): void
    {
        $this->baseWritableProduct()->setImages($imageCollection);
    }

    public function setPresentation(Presentation $presentation): void
    {
        $this->baseWritableProduct()->setPresentation($presentation);
    }

    public function setVariants(VariantCollection $variants): void
    {
        $this->baseWritableProduct()->setVariants($variants);
    }

    public function setExternalReference(string $externalReference): void
    {
        $this->baseWritableProduct()->setExternalReference($externalReference);
    }

    public function setEtag(string $etag): void
    {
        $this->baseWritableProduct()->setEtag($etag);
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->baseWritableProduct()->setUpdatedAt($updatedAt);
    }

    public function setUpdatedBy(string $updatedBy): void
    {
        $this->baseWritableProduct()->setUpdatedBy($updatedBy);
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->baseWritableProduct()->setCreatedAt($createdAt);
    }

    public function setVat(?Vat $vat): void
    {
        $this->baseWritableProduct()->setVat($vat);
    }

    public function setTaxExempt(?bool $taxExempt): void
    {
        $this->baseWritableProduct()->setTaxExempt($taxExempt);
    }

    public function setUsesDefaultTax(?bool $usesDefaultTax): void
    {
        $this->baseWritableProduct()->setUsesDefaultTax($usesDefaultTax);
    }

    public function setUnitName(string $unitName): void
    {
        $this->baseWritableProduct()->setUnitName($unitName);
    }

    /**
     * @param Metadata $metadata
     */
    public function setMetadata(Metadata $metadata): void
    {
        $this->baseWritableProduct()->setMetadata($metadata);
    }

    public function setVariantOptionDefinitions(VariantOptionDefinitions $definitions): void
    {
        $this->baseWritableProduct()->setVariantOptionDefinitions($definitions);
    }
}
