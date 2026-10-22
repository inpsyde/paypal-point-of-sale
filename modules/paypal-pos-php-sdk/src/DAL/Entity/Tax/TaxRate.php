<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Tax;

class TaxRate
{
    private string $uuid;
    private string $label;
    private ?float $percentage = null;
    private ?bool $default = null;
    public function __construct(string $uuid, string $label, ?float $percentage, ?bool $default)
    {
        $this->uuid = $uuid;
        $this->label = $label;
        $this->percentage = $percentage;
        $this->default = $default;
    }
    public function uuid(): string
    {
        return $this->uuid;
    }
    public function label(): string
    {
        return $this->label;
    }
    public function percentage(): ?float
    {
        return $this->percentage;
    }
    public function isDefault(): bool
    {
        return $this->default === \true;
    }
}
