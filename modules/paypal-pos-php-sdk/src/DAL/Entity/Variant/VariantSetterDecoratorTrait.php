<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
/**
 * Trait VariantSetterDecoratorTrait
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant
 */
trait VariantSetterDecoratorTrait
{
    abstract protected function baseWritableVariant(): WritableVariantInterface;
    public function setUuid(string $uuid): void
    {
        $this->baseWritableVariant()->setUuid($uuid);
    }
    public function setName(string $name): void
    {
        $this->baseWritableVariant()->setName($name);
    }
    public function setDescription(string $description): void
    {
        $this->baseWritableVariant()->setDescription($description);
    }
    public function setSku(string $sku): void
    {
        $this->baseWritableVariant()->setSku($sku);
    }
    public function setPrice(Price $price): void
    {
        $this->baseWritableVariant()->setPrice($price);
    }
    public function setVat(?Vat $vat): void
    {
        $this->baseWritableVariant()->setVat($vat);
    }
    public function setPresentation(Presentation $presentation): void
    {
        $this->baseWritableVariant()->setPresentation($presentation);
    }
    public function setOptions(VariantOptionCollection $options): void
    {
        $this->baseWritableVariant()->setOptions($options);
    }
    public function setUnitName(string $unitName): void
    {
        $this->baseWritableVariant()->setUnitName($unitName);
    }
    public function setCostPrice(Price $costPrice): void
    {
        $this->baseWritableVariant()->setCostPrice($costPrice);
    }
    public function setBarcode(string $barcode): void
    {
        $this->baseWritableVariant()->setBarcode($barcode);
    }
}
