<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Metrics;

interface MetricsSender
{
    public function sendMetrics(MetricsBucket $bucket): void;
}
