<?php

namespace Syde\Vendor\Zettle\Unleash\Client\DTO\Internal;

use Override;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Feature;
use Syde\Vendor\Zettle\Unleash\Client\DTO\FeatureDependency;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Variant;
/**
 * @internal
 */
final readonly class UnresolvedFeatureDependency implements FeatureDependency
{
    /**
     * @param array<Variant>|null $requiredVariants
     */
    public function __construct(private Feature $feature, private bool $expectedState, private ?array $requiredVariants)
    {
    }
    #[Override]
    public function getFeature(): Feature
    {
        return $this->feature;
    }
    #[Override]
    public function getExpectedState(): bool
    {
        return $this->expectedState;
    }
    #[Override]
    public function getRequiredVariants(): ?array
    {
        return $this->requiredVariants;
    }
    #[Override]
    public function isResolved(): bool
    {
        return \false;
    }
}
