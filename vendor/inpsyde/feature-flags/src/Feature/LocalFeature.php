<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Feature;

final readonly class LocalFeature implements Feature
{
    private string $label;
    public function __construct(private string $name, private bool $isEnabled, ?string $label = null, private string $description = '')
    {
        $this->label = $label ?? $name;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function getLabel(): string
    {
        return $this->label;
    }
    /**
     * Not part of the Feature interface, only in LocalFeature for simplicity.
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
}
