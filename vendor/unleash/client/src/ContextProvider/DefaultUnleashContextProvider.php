<?php

namespace Syde\Vendor\Zettle\Unleash\Client\ContextProvider;

use Syde\Vendor\Zettle\JetBrains\PhpStorm\Pure;
use Override;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\UnleashContext;
final readonly class DefaultUnleashContextProvider implements UnleashContextProvider
{
    #[Pure]
    #[Override]
    public function getContext(): Context
    {
        return new UnleashContext();
    }
}
