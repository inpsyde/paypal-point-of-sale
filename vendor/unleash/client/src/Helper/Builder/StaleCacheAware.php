<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper\Builder;

use Syde\Vendor\Zettle\Psr\SimpleCache\CacheInterface;
interface StaleCacheAware
{
    public function setStaleCache(CacheInterface $cache): void;
}
