<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product;

use DateTime;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata\Metadata;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\PropertyChangeAwareInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\PropertyChangeAwareTrait;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionDefinitions;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;

/**
 * Class Product
 * phpcs:disable Inpsyde.CodeQuality.PropertyPerClassLimit.TooMuchProperties
 * phpcs:disable Inpsyde.CodeQuality.NoAccessors
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product
 */
class Product implements ProductTransferInterface, PropertyChangeAwareInterface
{
    use PropertyChangeAwareTrait;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var ImageCollection
     */
    private $images;

    /**
     * @var VariantCollection
     */
    private $variants;

    /**
     * @var Presentation|null
     */
    private $presentation;

    /**
     * @var string|null
     */
    private $externalReference;

    /**
     * @var string|null
     */
    private $etag;

    /**
     * @var DateTime|null
     */
    private $updatedAt;

    /**
     * @var string|null
     */
    private $updatedBy;

    /**
     * @var DateTime|null
     */
    private $createdAt;

    /**
     * @var Vat|null
     */
    private $vat;

    /**
     * @var bool|null
     */
    private $taxExempt;

    /**
     * @var bool|null
     */
    private $usesDefaultTax;

    /**
     * @var string|null
     */
    private $unitName;

    /**
     * @var Metadata|null
     */
    private $metadata;

    /**
     * @var VariantOptionDefinitions
     */
    private $variantOptionDefinitions;

    /**
     * Product constructor.
     *
     * @param string $uuid
     * @param string $name
     * @param string $description
     * @param ImageCollection $images
     * @param VariantCollection $variants
     * @param Presentation|null $presentation
     * @param string|null $externalReference
     * @param string|null $etag
     * @param DateTime|null $updatedAt
     * @param string|null $updatedBy
     * @param DateTime|null $createdAt
     * @param Vat|null $vat
     * @param string|null $unitName
     * @param Metadata|null $metadata
     */
    public function __construct(
        string $uuid,
        string $name,
        string $description,
        ImageCollection $images,
        VariantCollection $variants,
        ?Presentation $presentation = null,
        ?string $externalReference = null,
        ?string $etag = null,
        ?DateTime $updatedAt = null,
        ?string $updatedBy = null,
        ?DateTime $createdAt = null,
        ?Vat $vat = null,
        ?bool $taxExempt = null,
        ?bool $usesDefaultTax = null,
        ?string $unitName = null,
        ?Metadata $metadata = null
    ) {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->description = $description;
        $this->images = $images;
        $this->variants = $variants;
        $this->presentation = $presentation;
        $this->externalReference = $externalReference;
        $this->etag = $etag;
        $this->updatedAt = $updatedAt;
        $this->updatedBy = $updatedBy;
        $this->createdAt = $createdAt;
        $this->vat = $vat;
        $this->taxExempt = $taxExempt;
        $this->usesDefaultTax = $usesDefaultTax;
        $this->unitName = $unitName;
        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function uuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;

        $this->addChangedProperties('uuid');
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;

        $this->addChangedProperties('name');
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;

        $this->addChangedProperties('description');
    }

    /**
     * @return ImageCollection
     */
    public function images(): ImageCollection
    {
        return $this->images;
    }

    /**
     * @param ImageCollection $imageCollection
     */
    public function setImages(ImageCollection $imageCollection): void
    {
        $this->images = $imageCollection;

        $this->addChangedProperties('images');
    }

    /**
     * @return VariantCollection
     */
    public function variants(): VariantCollection
    {
        return $this->variants;
    }

    /**
     * @param VariantCollection $variants
     */
    public function setVariants(VariantCollection $variants): void
    {
        $this->variants = $variants;

        $this->addChangedProperties('variants');
    }

    /**
     * @return Presentation|null
     */
    public function presentation(): ?Presentation
    {
        return $this->presentation;
    }

    /**
     * @param Presentation $presentation
     */
    public function setPresentation(Presentation $presentation): void
    {
        $this->presentation = $presentation;

        $this->addChangedProperties('presentation');
    }

    /**
     * @return string|null
     */
    public function externalReference(): ?string
    {
        return $this->externalReference;
    }

    /**
     * @param string $externalReference
     */
    public function setExternalReference(string $externalReference): void
    {
        $this->externalReference = $externalReference;

        $this->addChangedProperties('externalReference');
    }

    /**
     * @return string
     */
    public function etag(): ?string
    {
        return $this->etag;
    }

    /**
     * @param string $etag
     */
    public function setEtag(string $etag): void
    {
        $this->etag = $etag;

        $this->addChangedProperties('etag');
    }

    /**
     * @return DateTime
     */
    public function updatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;

        $this->addChangedProperties('updatedAt');
    }

    /**
     * @return string
     */
    public function updatedBy(): ?string
    {
        return $this->updatedBy;
    }

    /**
     * @param string $updatedBy
     */
    public function setUpdatedBy(string $updatedBy): void
    {
        $this->updatedBy = $updatedBy;

        $this->addChangedProperties('updatedBy');
    }

    /**
     * @return DateTime|null
     */
    public function createdAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;

        $this->addChangedProperties('createdAt');
    }

    /**
     * @return Vat|null
     */
    public function vat(): ?Vat
    {
        return $this->vat;
    }

    /**
     * @param Vat $vat
     */
    public function setVat(?Vat $vat): void
    {
        $this->vat = $vat;

        $this->addChangedProperties('vat');
    }

    public function taxExempt(): ?bool
    {
        return $this->taxExempt;
    }

    public function setTaxExempt(?bool $taxExempt): void
    {
        $this->taxExempt = $taxExempt;
    }

    public function usesDefaultTax(): ?bool
    {
        return $this->usesDefaultTax;
    }

    public function setUsesDefaultTax(?bool $usesDefaultTax): void
    {
        $this->usesDefaultTax = $usesDefaultTax;
    }

    /**
     * @return string|null
     */
    public function unitName(): ?string
    {
        return $this->unitName;
    }

    /**
     * @param string $unitName
     */
    public function setUnitName(string $unitName): void
    {
        $this->unitName = $unitName;

        $this->addChangedProperties('unitName');
    }

    /**
     * @return Metadata|null
     */
    public function metadata(): ?Metadata
    {
        return $this->metadata;
    }

    /**
     * @param Metadata $metadata
     */
    public function setMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;

        $this->addChangedProperties('metadata');
    }

    /**
     * @inheritDoc
     */
    public function variantOptionDefinitions(): ?VariantOptionDefinitions
    {
        return $this->variantOptionDefinitions;
    }

    public function setVariantOptionDefinitions(
        VariantOptionDefinitions $definitions
    ): void {
        $this->variantOptionDefinitions = $definitions;
    }
}
