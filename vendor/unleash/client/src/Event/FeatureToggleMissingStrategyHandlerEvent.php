<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Event;

use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Feature;
final class FeatureToggleMissingStrategyHandlerEvent extends AbstractEvent
{
    /**
     * @internal
     */
    public function __construct(private readonly Context $context, private readonly Feature $feature)
    {
    }
    public function getContext(): Context
    {
        return $this->context;
    }
    public function getFeature(): Feature
    {
        return $this->feature;
    }
}
