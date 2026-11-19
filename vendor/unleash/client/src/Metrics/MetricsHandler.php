<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Metrics;

use Syde\Vendor\Zettle\Unleash\Client\DTO\Feature;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Variant;
interface MetricsHandler
{
    public function handleMetrics(Feature $feature, bool $successful, ?Variant $variant = null): void;
}
