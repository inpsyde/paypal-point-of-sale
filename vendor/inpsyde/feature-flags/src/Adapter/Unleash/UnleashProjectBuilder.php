<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash;

use Syde\Vendor\Zettle\Http\Discovery\Psr17FactoryDiscovery;
use Syde\Vendor\Zettle\Http\Discovery\Psr18ClientDiscovery;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Cache\MetricsBuffer;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Cache\OptionsCache;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Http\FeatureResponseCheck;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Http\MetricsSendGate;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionMonitor;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionSource;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestFactoryInterface;
use Syde\Vendor\Zettle\Unleash\Client\Enum\CacheKey;
use Syde\Vendor\Zettle\Unleash\Client\Unleash;
use Syde\Vendor\Zettle\Unleash\Client\UnleashBuilder;
/**
 * Immutable builder for UnleashProject. Every with*() method returns a new instance.
 *
 * With the defaults, a site sends at most about 105 requests to Unleash per month: the flag
 * list is fetched about twice a day, metrics are sent once a day, and the client doesn't register.
 */
// A builder has one property per setting.
// phpcs:ignore Syde.Classes.PropertyLimit.TooManyProperties
final class UnleashProjectBuilder
{
    public const DEFAULT_FRESH_TTL = 43200;
    // 12 hours
    public const DEFAULT_STALE_TTL = 604800;
    // 7 days
    public const DEFAULT_TTL_JITTER = 0.2;
    public const DEFAULT_METRICS_INTERVAL = 86400;
    // 1 day
    private string $appUrl = '';
    private string $apiToken = '';
    private ?string $appName = null;
    private ?string $instanceId = null;
    private ?ClientInterface $httpClient = null;
    private ?RequestFactoryInterface $requestFactory = null;
    private int $freshTtl = self::DEFAULT_FRESH_TTL;
    private int $staleTtl = self::DEFAULT_STALE_TTL;
    private float $ttlJitter = self::DEFAULT_TTL_JITTER;
    private bool $metricsEnabled = \true;
    private int $metricsInterval = self::DEFAULT_METRICS_INTERVAL;
    private RegistrationMode $registration = RegistrationMode::Off;
    private ?string $targetingId = null;
    /**
     * @var array<string, string>
     */
    private array $properties = [];
    private ?string $packageVersion = null;
    /**
     * @var (\Closure(): int)|null
     */
    private ?\Closure $clock = null;
    public function __construct(private readonly string $project)
    {
    }
    /**
     * The Unleash API URL, e.g. https://unleash.example.com/api
     */
    public function withAppUrl(string $appUrl): self
    {
        $clone = clone $this;
        $clone->appUrl = $appUrl;
        return $clone;
    }
    /**
     * The client API token. It selects the Unleash environment.
     */
    public function withApiToken(string $apiToken): self
    {
        $clone = clone $this;
        $clone->apiToken = $apiToken;
        return $clone;
    }
    /**
     * The app name reported to Unleash. Defaults to the project name.
     */
    public function withAppName(string $appName): self
    {
        $clone = clone $this;
        $clone->appName = $appName;
        return $clone;
    }
    /**
     * The SDK instance ID reported to Unleash. Defaults to the project name. Keep it the same
     * on all sites, or Unleash lists every site as an instance. Use withTargetingId() to tell
     * sites apart in flag rules.
     */
    public function withInstanceId(string $instanceId): self
    {
        $clone = clone $this;
        $clone->instanceId = $instanceId;
        return $clone;
    }
    /**
     * Pass an HTTP client and request factory to keep the builder from looking for
     * implementations via php-http/discovery.
     */
    public function withHttpClient(ClientInterface $httpClient, RequestFactoryInterface $requestFactory): self
    {
        $clone = clone $this;
        $clone->httpClient = $httpClient;
        $clone->requestFactory = $requestFactory;
        return $clone;
    }
    /**
     * How long the flag list is used before it is fetched again.
     */
    public function withFreshTtl(int $seconds): self
    {
        $clone = clone $this;
        $clone->freshTtl = $seconds;
        return $clone;
    }
    /**
     * How long the last successful response serves as a fallback while Unleash can't be
     * reached. Must be longer than the fresh TTL plus the TTL jitter.
     */
    public function withStaleTtl(int $seconds): self
    {
        $clone = clone $this;
        $clone->staleTtl = $seconds;
        return $clone;
    }
    /**
     * Randomises the fresh TTL by up to this ratio in either direction, so sites don't
     * refresh in sync. 0.2 stands for ±20%.
     */
    public function withTtlJitter(float $ratio): self
    {
        $clone = clone $this;
        $clone->ttlJitter = $ratio;
        return $clone;
    }
    public function withMetrics(bool $enabled, int $intervalSeconds = self::DEFAULT_METRICS_INTERVAL): self
    {
        $clone = clone $this;
        $clone->metricsEnabled = $enabled;
        $clone->metricsInterval = $intervalSeconds;
        return $clone;
    }
    public function withRegistration(RegistrationMode $mode): self
    {
        $clone = clone $this;
        $clone->registration = $mode;
        return $clone;
    }
    /**
     * Identifies the site in the evaluation context, as userId (so gradual rollouts are sticky
     * per site) and as siteId (for constraints and segments). Required. Every site downloads
     * the flag rules, so use an ID that can't be traced back to the site.
     */
    public function withTargetingId(string $id): self
    {
        $clone = clone $this;
        $clone->targetingId = $id;
        return $clone;
    }
    /**
     * Additional evaluation context properties, e.g. ['qaSite' => 'true']. The names of the
     * SDK's context fields (userId, sessionId, remoteAddress, environment, hostname,
     * currentTime) and siteId and packageVersion are reserved.
     *
     * @param array<string, string> $properties
     */
    public function withProperties(array $properties): self
    {
        $clone = clone $this;
        $clone->properties = $properties;
        return $clone;
    }
    /**
     * The version of the consuming plugin. Used for per-version registration and as the
     * packageVersion context field.
     */
    public function withPackageVersion(string $version): self
    {
        $clone = clone $this;
        $clone->packageVersion = $version;
        return $clone;
    }
    /**
     * Replaces time() for the cache and the connection status, e.g. to simulate days in tests.
     *
     * @param \Closure(): int $clock Returns the current Unix timestamp.
     */
    public function withClock(\Closure $clock): self
    {
        $clone = clone $this;
        $clone->clock = $clock;
        return $clone;
    }
    /**
     * Never throws for runtime failures: if the SDK client can't be built, e.g. because no
     * HTTP client is available, the project answers every flag check with the caller's
     * default and the connection status reports the error.
     *
     * @throws \InvalidArgumentException If the settings contradict each other.
     */
    public function build(): UnleashProject
    {
        $this->validate();
        $project = $this->project;
        [$appUrl, $apiToken, $appName, $instanceId] = $this->connectionSettings();
        $clock = $this->clock ?? time(...);
        $cache = new OptionsCache($project, OptionsCache::connectionId($appUrl, $apiToken, $appName), $clock, $this->ttlJitter);
        $monitor = new ConnectionMonitor($cache, $apiToken, $clock);
        $cache->onResponseStored($monitor->recordSuccess(...));
        $metricsBuffer = new MetricsBuffer($cache, $clock);
        [$builder, $metricsGate] = $this->sdkBuilder($appUrl, $appName, $instanceId, $cache);
        $builder = $builder->withCacheHandler($cache, $this->freshTtl)->withStaleCacheHandler($cache)->withStaleTtl($this->staleTtl)->withBootstrap(['version' => 1, 'features' => []])->withMetricsEnabled($this->metricsEnabled)->withMetricsInterval($this->metricsInterval * 1000)->withMetricsCacheHandler($metricsBuffer)->withAutomaticRegistrationEnabled(\false)->withEventSubscriber($monitor)->withHeader('Authorization', $apiToken);
        try {
            $repository = $builder->buildRepository();
            $client = $builder->build();
        } catch (\Throwable $error) {
            $monitor->recordFailure($error, ConnectionSource::Defaults);
            return new UnleashProject($project, $monitor);
        }
        add_action('shutdown', [$metricsBuffer, 'flush']);
        if ($metricsGate) {
            add_action('shutdown', $metricsGate->sendPending(...));
        }
        if ($this->registration === RegistrationMode::PerVersion) {
            // Registering sends a request, so it shouldn't delay the page.
            add_action('shutdown', fn() => $this->registerPerVersion($client, $cache));
        }
        return new UnleashProject($project, $monitor, $repository, $client);
    }
    /**
     * The builder values, with the app name and instance ID defaulting to the project name.
     *
     * @return array{string, string, string, string} URL, API token, app name and instance ID.
     */
    private function connectionSettings(): array
    {
        return [$this->appUrl, $this->apiToken, $this->appName ?? $this->project, $this->instanceId ?? $this->project];
    }
    /**
     * @return array{UnleashBuilder, MetricsSendGate|null} The gate wraps the HTTP client, if
     *     there is one.
     */
    private function sdkBuilder(string $appUrl, string $appName, string $instanceId, OptionsCache $cache): array
    {
        $builder = UnleashBuilder::create()->withAppUrl($appUrl)->withAppName($appName)->withInstanceId($instanceId)->withContextProvider(new ContextProvider($this->project, (string) $this->targetingId, $this->packageVersion ?? '0.0.0', $this->properties));
        $httpClient = $this->httpClient ?? self::discover(Psr18ClientDiscovery::find(...));
        $requestFactory = $this->requestFactory ?? self::discover(Psr17FactoryDiscovery::findRequestFactory(...));
        if (!$httpClient || !$requestFactory) {
            return [$builder, null];
        }
        // Half the interval: the SDK drops the bucket of a skipped send, so a claim that
        // blocked a due send by a few seconds would lose a whole interval of counts.
        $metricsInterval = intdiv($this->metricsInterval, 2);
        $metricsGate = new MetricsSendGate(new FeatureResponseCheck($httpClient), static fn(): bool => $cache->claimMetricsSend($metricsInterval));
        return [$builder->withHttpClient($metricsGate)->withRequestFactory($requestFactory), $metricsGate];
    }
    /**
     * Without an implementation, the SDK's own lookup fails as well, and build() reports it.
     *
     * @template T of object
     * @param \Closure(): T $find
     * @return T|null
     */
    private static function discover(\Closure $find): ?object
    {
        try {
            return $find();
        } catch (\Throwable) {
            return null;
        }
    }
    /**
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        $errors = [];
        if ($this->freshTtl <= 0) {
            $errors[] = 'The fresh TTL must be positive.';
        }
        // The flag list can be up to the jitter older than the fresh TTL when it is refetched.
        if ($this->staleTtl <= $this->freshTtl * (1 + $this->ttlJitter)) {
            $errors[] = 'The stale TTL must be longer than the fresh TTL plus the TTL jitter.';
        }
        if ($this->ttlJitter < 0 || $this->ttlJitter >= 1) {
            $errors[] = 'The TTL jitter must be at least 0 and less than 1.';
        }
        if ($this->metricsInterval <= 0) {
            $errors[] = 'The metrics interval must be positive.';
        }
        if ($this->registration === RegistrationMode::PerVersion && $this->packageVersion === null) {
            $errors[] = 'Per-version registration needs a package version.';
        }
        if ($this->targetingId === null || $this->targetingId === '') {
            // Without it, every site gets the same result from gradual rollouts and variants.
            $errors[] = 'A targeting ID is required, see withTargetingId().';
        }
        $errors = [...$errors, ...$this->propertyErrors()];
        if ($errors) {
            throw new \InvalidArgumentException("Invalid settings for Unleash project \"{$this->project}\": " . implode(' ', $errors));
        }
    }
    /**
     * @return list<string>
     */
    private function propertyErrors(): array
    {
        $errors = [];
        // The array comes from the consumer, possibly ignoring the declared types.
        foreach ($this->properties as $name => $value) {
            $errors[] = match (\true) {
                // PHP turns numeric string keys into integers.
                !is_string($name) => "The property name \"{$name}\" must not be numeric.",
                $name === '' => 'Property names must not be empty.',
                ContextProvider::isReserved($name) => "The property name \"{$name}\" is reserved.",
                default => null,
            };
            if (!is_string($value)) {
                $errors[] = "The property \"{$name}\" must be a string.";
            }
        }
        return array_values(array_filter($errors, 'is_string'));
    }
    private function registerPerVersion(Unleash $client, OptionsCache $cache): void
    {
        $version = (string) $this->packageVersion;
        if ($cache->get(OptionsCache::KEY_REGISTERED_VERSION) === $version) {
            return;
        }
        // Stored first, so a failing registration isn't retried on every request.
        $cache->set(OptionsCache::KEY_REGISTERED_VERSION, $version);
        // The SDK skips registering while it has a cached registration.
        $cache->delete(CacheKey::REGISTRATION);
        try {
            $client->register();
        } catch (\Throwable) {
            // Registration is optional. Flag checks don't depend on it.
        }
    }
}
