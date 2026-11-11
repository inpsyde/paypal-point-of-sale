<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper\Builder;

use Syde\Vendor\Zettle\Unleash\Client\Stickiness\StickinessCalculator;
interface StickinessCalculatorAware
{
    public function setStickinessCalculator(StickinessCalculator $stickinessCalculator): void;
}
