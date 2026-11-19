<?php

namespace Syde\Vendor\Zettle\Unleash\Client\ContextProvider;

use Syde\Vendor\Zettle\JetBrains\PhpStorm\Pure;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
interface UnleashContextProvider
{
    #[Pure]
    public function getContext(): Context;
}
