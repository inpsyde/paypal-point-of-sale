<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Status;

/**
 * The last error that kept a project from getting flags from Unleash. The message is
 * truncated and never contains the API token.
 */
final readonly class ConnectionError
{
    public function __construct(public int $time, public string $exceptionClass, public ?int $httpStatus, public string $message)
    {
    }
}
