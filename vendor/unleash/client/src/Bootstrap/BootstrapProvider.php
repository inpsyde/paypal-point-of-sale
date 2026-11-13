<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Bootstrap;

use JsonSerializable;
use Traversable;
interface BootstrapProvider
{
    /**
     * @return array<mixed>|JsonSerializable|Traversable<mixed>|null
     */
    public function getBootstrap(): array|JsonSerializable|Traversable|null;
}
