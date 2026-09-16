<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
class VariantOption
{
    private string $name;
    private string $value;
    private ?ImageInterface $image;
    public function __construct(string $name, string $value, ?ImageInterface $image = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->image = $image;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function setName(string $name): VariantOption
    {
        $this->name = $name;
        return $this;
    }
    public function value(): string
    {
        return $this->value;
    }
    public function setValue(string $value): VariantOption
    {
        $this->value = $value;
        return $this;
    }
    public function image(): ?ImageInterface
    {
        return $this->image;
    }
    public function setImage(ImageInterface $image): VariantOption
    {
        $this->image = $image;
        return $this;
    }
}
