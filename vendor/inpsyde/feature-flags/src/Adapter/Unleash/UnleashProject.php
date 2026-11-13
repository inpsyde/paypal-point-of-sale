<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionMonitor;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionStatus;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Feature\Feature;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\FeatureFlags;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Project\Project;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Feature as UnleashFeatureDto;
use Syde\Vendor\Zettle\Unleash\Client\Repository\UnleashRepository;
use Syde\Vendor\Zettle\Unleash\Client\Unleash;
final class UnleashProject implements Project
{
    /**
     * Prefix of the adapter's context filters: "{HOOK_BASE}.context" and
     * "{HOOK_BASE}.{project}.context".
     */
    public const HOOK_BASE = FeatureFlags::HOOK_BASE . '.unleash';
    /**
     * @internal Use UnleashProject::builder(). Without an SDK client (because it could not be
     *     built), every flag check returns the caller's default.
     */
    public function __construct(private readonly string $name, private readonly ConnectionMonitor $monitor, private readonly ?UnleashRepository $repository = null, private readonly ?Unleash $client = null)
    {
    }
    public static function builder(string $project): UnleashProjectBuilder
    {
        return new UnleashProjectBuilder($project);
    }
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * Not cached per instance: the result depends on the default and on the context at
     * the time of the check, e.g. the current user.
     */
    public function isEnabled(string $flag, bool $default = \false): bool
    {
        if (!$this->client) {
            return $default;
        }
        try {
            return $this->client->isEnabled($flag, null, $default);
        } catch (\Throwable) {
            return $default;
        }
    }
    public function getFeatures(): array
    {
        if (!$this->repository) {
            return [];
        }
        try {
            $features = $this->repository->getFeatures();
        } catch (\Throwable) {
            return [];
        }
        $features = is_array($features) ? $features : iterator_to_array($features);
        return array_map(static fn(UnleashFeatureDto $feature): Feature => new UnleashFeature($feature->getName()), array_values($features));
    }
    /**
     * Whether flags currently come from Unleash, from the stale copy of an earlier response,
     * or are the callers' defaults, and the last error.
     */
    public function connectionStatus(): ConnectionStatus
    {
        return $this->monitor->status();
    }
}
