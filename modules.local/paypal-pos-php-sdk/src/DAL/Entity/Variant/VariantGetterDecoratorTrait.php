<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;

trait VariantGetterDecoratorTrait
{

    abstract protected function baseVariant(): VariantInterface;

    public function uuid(): string
    {
        return $this->baseVariant()->uuid();
    }

    public function name(): string
    {
        return $this->baseVariant()->name();
    }

    public function description(): string
    {
        return $this->baseVariant()->description();
    }

    public function sku(): string
    {
        return $this->baseVariant()->sku();
    }

    public function price(): ?Price
    {
        return $this->baseVariant()->price();
    }

    public function vat(): ?Vat
    {
        return $this->baseVariant()->vat();
    }

    public function presentation(): ?Presentation
    {
        return $this->baseVariant()->presentation();
    }

    public function unitName(): ?string
    {
        return $this->baseVariant()->unitName();
    }

    public function options(): ?VariantOptionCollection
    {
        return $this->baseVariant()->options();
    }

    public function costPrice(): ?Price
    {
        return $this->baseVariant()->costPrice();
    }

    public function barcode(): ?string
    {
        return $this->baseVariant()->barcode();
    }
}
