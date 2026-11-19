<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Repository;

use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\ProxyFeature;
interface ProxyRepository
{
    public function findFeatureByContext(string $featureName, ?Context $context = null): ?ProxyFeature;
}
