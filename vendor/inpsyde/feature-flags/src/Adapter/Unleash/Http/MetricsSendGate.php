<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Http;

use Syde\Vendor\Zettle\Psr\Http\Client\ClientExceptionInterface;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
/**
 * Wraps the SDK's HTTP client, so a site sends its metrics once per interval and no visitor's
 * page waits for the request.
 *
 * The SDK sends a due metrics bucket during a flag check and deletes it after the response.
 * Every request that loads the bucket before then sends it too, so a busy site would send the
 * same metrics many times. Here, a request first has to claim the send, which add_option()
 * decides within milliseconds. The request that claims it sends the metrics on shutdown, after
 * the page; the others drop their bucket, as metrics are a sample anyway.
 *
 * The SDK ignores client errors when it sends metrics, so both a deferred and a skipped send
 * are reported to it as MetricsNotSent.
 *
 * @internal Built by UnleashProjectBuilder, which calls sendPending() on shutdown.
 */
final class MetricsSendGate implements ClientInterface
{
    private const METRICS_PATH = '/client/metrics';
    /**
     * @var list<RequestInterface>
     */
    private array $pending = [];
    /**
     * @param \Closure(): bool $claim Claims the send for this request, see
     *     OptionsCache::claimMetricsSend().
     */
    public function __construct(private readonly ClientInterface $client, private readonly \Closure $claim)
    {
    }
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST' || !str_ends_with(rtrim($request->getUri()->getPath(), '/'), self::METRICS_PATH)) {
            return $this->client->sendRequest($request);
        }
        if (!($this->claim)()) {
            throw new MetricsNotSent($request, 'Another request sends the metrics.');
        }
        $this->pending[] = $request;
        throw new MetricsNotSent($request, 'The metrics are sent on shutdown.');
    }
    /**
     * Sends the metrics claimed in this request.
     */
    public function sendPending(): void
    {
        $pending = $this->pending;
        $this->pending = [];
        foreach ($pending as $request) {
            try {
                $this->client->sendRequest($request);
            } catch (ClientExceptionInterface) {
                // Ignored, like the SDK does when it sends metrics itself.
            }
        }
    }
}
