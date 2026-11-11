<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper\Builder;

use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
interface HttpClientAware
{
    public function setHttpClient(ClientInterface $client): void;
}
