<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Bootstrap;

interface BootstrapHandler
{
    public function getBootstrapContents(BootstrapProvider $provider): ?string;
}
