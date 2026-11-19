<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Event;

use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Feature;
final class FeatureToggleDisabledEvent extends AbstractEvent
{
    /**
     * @internal
     */
    public function __construct(private readonly Feature $feature, private readonly Context $context)
    {
    }
    public function getFeature(): Feature
    {
        return $this->feature;
    }
    public function getContext(): Context
    {
        return $this->context;
    }
}
