<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper\Builder;

use Syde\Vendor\Zettle\Psr\SimpleCache\CacheInterface;
interface CacheAware
{
    public function setCache(CacheInterface $cache): void;
}
