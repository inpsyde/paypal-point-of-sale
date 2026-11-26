<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Status;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Cache\OptionsCache;
use Syde\Vendor\Zettle\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Syde\Vendor\Zettle\Unleash\Client\Enum\CacheKey;
use Syde\Vendor\Zettle\Unleash\Client\Event\FetchingDataFailedEvent;
use Syde\Vendor\Zettle\Unleash\Client\Event\UnleashEvents;
/**
 * Records the connection status of a project in its options cache: successful fetches
 * (via OptionsCache, when it stores the response) and failed ones (via the SDK's events).
 *
 * @internal Built by UnleashProjectBuilder. Read the status with
 *     UnleashProject::connectionStatus().
 */
final class ConnectionMonitor implements EventSubscriberInterface
{
    private const MAX_MESSAGE_LENGTH = 200;
    // A repeated failure, e.g. a missing HTTP client on every request, is stored again only
    // after this many seconds, so it doesn't cause a database write on every request.
    private const REPEATED_ERROR_INTERVAL = 3600;
    /**
     * @param \Closure(): int $clock
     */
    public function __construct(private readonly OptionsCache $cache, private readonly string $apiToken, private readonly \Closure $clock)
    {
    }
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [UnleashEvents::FETCHING_DATA_FAILED => 'onFetchingDataFailed'];
    }
    public function onFetchingDataFailed(FetchingDataFailedEvent $event): void
    {
        $source = $this->cache->has(CacheKey::FEATURES_RESPONSE) ? ConnectionSource::Stale : ConnectionSource::Defaults;
        $this->recordFailure($event->getException(), $source);
    }
    public function recordSuccess(): void
    {
        $this->store(new ConnectionStatus(ConnectionSource::Fresh, ($this->clock)(), $this->status()->lastError));
    }
    public function recordFailure(\Throwable $error, ConnectionSource $source): void
    {
        $current = $this->status();
        $now = ($this->clock)();
        $lastError = new ConnectionError($now, $error::class, $this->httpStatus($error), $this->sanitizeMessage($error->getMessage()));
        $previous = $current->lastError;
        $repeated = $previous && $current->source === $source && $previous->exceptionClass === $lastError->exceptionClass && $previous->httpStatus === $lastError->httpStatus && $previous->message === $lastError->message && $now - $previous->time < self::REPEATED_ERROR_INTERVAL;
        if ($repeated) {
            return;
        }
        $this->store(new ConnectionStatus($source, $current->lastSuccessTime, $lastError));
    }
    public function status(): ConnectionStatus
    {
        $stored = $this->cache->get(OptionsCache::KEY_CONNECTION_STATUS);
        return $stored instanceof ConnectionStatus ? $stored : new ConnectionStatus(ConnectionSource::Unknown);
    }
    private function store(ConnectionStatus $status): void
    {
        $this->cache->set(OptionsCache::KEY_CONNECTION_STATUS, $status);
    }
    /**
     * The SDK reports unexpected status codes as "Invalid status code: '503'".
     */
    private function httpStatus(\Throwable $error): ?int
    {
        if (!preg_match("/status code: '(\\d{3})'/", $error->getMessage(), $matches)) {
            return null;
        }
        return (int) $matches[1];
    }
    private function sanitizeMessage(string $message): string
    {
        if ($this->apiToken !== '') {
            $message = str_replace($this->apiToken, '[token]', $message);
        }
        return mb_substr($message, 0, self::MAX_MESSAGE_LENGTH);
    }
}
