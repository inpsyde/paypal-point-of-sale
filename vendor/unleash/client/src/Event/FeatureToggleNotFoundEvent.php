<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Event;

use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
final class FeatureToggleNotFoundEvent extends AbstractEvent
{
    /**
     * @internal
     */
    public function __construct(private readonly Context $context, private readonly string $featureName)
    {
    }
    public function getContext(): Context
    {
        return $this->context;
    }
    public function getFeatureName(): string
    {
        return $this->featureName;
    }
}
