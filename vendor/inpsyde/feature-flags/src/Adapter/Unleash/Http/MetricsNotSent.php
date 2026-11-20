<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Http;

use Syde\Vendor\Zettle\Psr\Http\Client\RequestExceptionInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
/**
 * A metrics request that MetricsSendGate didn't send during the flag check: it is either sent
 * on shutdown or not at all.
 */
final class MetricsNotSent extends \RuntimeException implements RequestExceptionInterface
{
    public function __construct(private readonly RequestInterface $request, string $message)
    {
        parent::__construct($message);
    }
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
