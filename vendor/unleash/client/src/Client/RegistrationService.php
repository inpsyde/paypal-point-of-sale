<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Client;

use Syde\Vendor\Zettle\Unleash\Client\Strategy\StrategyHandler;
interface RegistrationService
{
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     */
    public function register(iterable $strategyHandlers): bool;
}
