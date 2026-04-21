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

trait ProductGetterDecoratorTrait
{
    abstract protected function baseProduct(): ProductInterface;

    public function uuid(): string
    {
        return $this->baseProduct()->uuid();
    }

    public function name(): string
    {
        return $this->baseProduct()->name();
    }

    public function description(): string
    {
        return $this->baseProduct()->description();
    }

    public function images(): ImageCollection
    {
        return $this->baseProduct()->images();
    }

    public function presentation(): ?Presentation
    {
        return $this->baseProduct()->presentation();
    }

    public function variants(): VariantCollection
    {
        return $this->baseProduct()->variants();
    }

    public function externalReference(): ?string
    {
        return $this->baseProduct()->externalReference();
    }

    public function etag(): ?string
    {
        return $this->baseProduct()->etag();
    }

    public function updatedAt(): ?DateTime
    {
        return $this->baseProduct()->updatedAt();
    }

    public function updatedBy(): ?string
    {
        return $this->baseProduct()->updatedBy();
    }

    public function createdAt(): ?DateTime
    {
        return $this->baseProduct()->createdAt();
    }

    public function vat(): ?Vat
    {
        return $this->baseProduct()->vat();
    }

    public function taxExempt(): ?bool
    {
        return $this->baseProduct()->taxExempt();
    }

    public function usesDefaultTax(): ?bool
    {
        return $this->baseProduct()->usesDefaultTax();
    }

    public function unitName(): ?string
    {
        return $this->baseProduct()->unitName();
    }

    /**
     * @return Metadata
     */
    public function metadata(): Metadata
    {
        return $this->baseProduct()->metadata();
    }

    public function variantOptionDefinitions(): ?VariantOptionDefinitions
    {
        return $this->baseProduct()->variantOptionDefinitions();
    }
}
