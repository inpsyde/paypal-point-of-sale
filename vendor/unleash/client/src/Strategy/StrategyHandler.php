<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Strategy;

use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Strategy;
interface StrategyHandler
{
    public function supports(Strategy $strategy): bool;
    public function getStrategyName(): string;
    public function isEnabled(Strategy $strategy, Context $context): bool;
}
