<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Metrics;

interface MetricsBucketSerializer
{
    public function serialize(MetricsBucket $bucket): string;
    public function deserialize(string $serialized): MetricsBucket;
}
