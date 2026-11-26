<?php

namespace Syde\Vendor\Zettle\Unleash\Client\DTO\Internal;

use Override;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Feature;
use Syde\Vendor\Zettle\Unleash\Client\DTO\FeatureDependency;
/**
 * @internal
 */
final readonly class UnresolvedFeature implements Feature
{
    public function __construct(private string $name)
    {
    }
    #[Override]
    public function getName(): string
    {
        return $this->name;
    }
    #[Override]
    public function isEnabled(): bool
    {
        return \false;
    }
    #[Override]
    public function getStrategies(): iterable
    {
        return [];
    }
    #[Override]
    public function getVariants(): array
    {
        return [];
    }
    #[Override]
    public function hasImpressionData(): bool
    {
        return \false;
    }
    /**
     * @return array<FeatureDependency>
     */
    public function getDependencies(): array
    {
        return [];
    }
}
