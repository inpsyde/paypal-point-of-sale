<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper\Builder;

use Syde\Vendor\Zettle\Unleash\Client\Configuration\UnleashConfiguration;
interface ConfigurationAware
{
    public function setConfiguration(UnleashConfiguration $configuration): void;
}
