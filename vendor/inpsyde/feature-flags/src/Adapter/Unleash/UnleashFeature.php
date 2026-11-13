<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Feature\Feature;
final readonly class UnleashFeature implements Feature
{
    public function __construct(private string $name, private string $description = '')
    {
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
        return $this->name;
    }
}
