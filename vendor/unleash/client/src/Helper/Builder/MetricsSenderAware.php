<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper\Builder;

use Syde\Vendor\Zettle\Unleash\Client\Metrics\MetricsSender;
interface MetricsSenderAware
{
    public function setMetricsSender(MetricsSender $metricsSender): void;
}
