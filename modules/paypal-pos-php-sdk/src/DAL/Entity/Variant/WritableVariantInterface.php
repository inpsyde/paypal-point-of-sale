<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\VatSetterInterface;
/**
 * Interface WritableVariantInterface
 * phpcs:disable Inpsyde.CodeQuality.NoAccessors
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant
 */
interface WritableVariantInterface extends VatSetterInterface
{
    public function setUuid(string $uuid): void;
    public function setName(string $name): void;
    public function setDescription(string $description): void;
    public function setSku(string $sku): void;
    public function setPrice(?Price $price): void;
    public function setPresentation(Presentation $presentation): void;
    public function setOptions(VariantOptionCollection $options): void;
    public function setUnitName(string $unitName): void;
    public function setCostPrice(Price $costPrice): void;
    public function setBarcode(string $barcode): void;
}
