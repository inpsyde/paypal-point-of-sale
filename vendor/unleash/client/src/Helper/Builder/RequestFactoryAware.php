<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper\Builder;

use Syde\Vendor\Zettle\Psr\Http\Message\RequestFactoryInterface;
interface RequestFactoryAware
{
    public function setRequestFactory(RequestFactoryInterface $requestFactory): void;
}
