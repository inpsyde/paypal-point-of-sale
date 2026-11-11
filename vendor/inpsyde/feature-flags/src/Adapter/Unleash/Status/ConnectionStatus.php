<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Status;

final readonly class ConnectionStatus
{
    public function __construct(public ConnectionSource $source, public ?int $lastSuccessTime = null, public ?ConnectionError $lastError = null)
    {
    }
}
