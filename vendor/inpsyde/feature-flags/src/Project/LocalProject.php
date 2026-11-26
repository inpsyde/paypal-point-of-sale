<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Project;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Feature\LocalFeature;
final readonly class LocalProject implements Project
{
    /**
     * @var array<string, LocalFeature>
     */
    private array $features;
    public function __construct(private string $name, LocalFeature ...$features)
    {
        $byName = [];
        foreach ($features as $feature) {
            $byName[$feature->getName()] = $feature;
        }
        $this->features = $byName;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function isEnabled(string $flag, bool $default = \false): bool
    {
        return ($this->features[$flag] ?? null)?->isEnabled() ?? $default;
    }
    public function findFeature(string $feature): LocalFeature
    {
        if (!isset($this->features[$feature])) {
            throw new \RuntimeException("Feature {$feature} not found");
        }
        return $this->features[$feature];
    }
    public function getFeatures(): array
    {
        return $this->features;
    }
}
