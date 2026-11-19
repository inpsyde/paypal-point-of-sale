<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags;

use Inpsyde\FeatureFlags\Adapter\Unleash\RegistrationMode;
use Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionSource;
use Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionStatus;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProject;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProjectBuilder;
use Inpsyde\FeatureFlags\FeatureFlags;
use Inpsyde\FeatureFlags\Project\Project;
use Inpsyde\FeatureFlags\SiteId;
use Inpsyde\Wp\HttpClient\Client as WpHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * The PayPal POS feature flags. The project is registered before the modules are loaded, so
 * flags can decide whether a module is active (see FlaggedModule), and checks work from any
 * code, including other modules' service definitions.
 *
 * Checks go through the project's filter, so snippets and support can override a flag with
 * a callback at a priority above 10.
 */
final class Flags
{
    /**
     * Name of the project in the flag filters (syde.feature-flags.paypal-pos) and in the
     * option names. The API token selects the Unleash project and environment.
     */
    public const PROJECT = 'paypal-pos';

    private function __construct(
        private readonly Settings $settings,
        private readonly string $siteId,
        private readonly ?Project $project,
        private readonly ?string $configurationError
    ) {
    }

    /**
     * Connects the plugin to Unleash, with the settings of the site. Never throws: a
     * configuration error must not take the shop down. Flag checks then return their
     * default, and the error is kept for the log and the status report.
     */
    public static function boot(string $pluginVersion): self
    {
        $settings = Settings::fromEnvironment();
        $siteId = SiteId::forCurrentSite();

        try {
            $factory = new Psr17Factory();
            $builder = self::builder(
                $siteId,
                $pluginVersion,
                $settings->qaSite,
                // WordPress' HTTP API, so proxy settings and the http_* filters apply.
                new WpHttpClient(new \WP_Http(), $factory, $factory, $factory),
                $factory
            );
        } catch (\Throwable $error) {
            return new self($settings, $siteId, null, $error->getMessage());
        }

        return self::connect($builder, $settings, $siteId);
    }

    /**
     * The request settings and the context, without the connection. They keep each site
     * within its share of the Unleash request budget.
     */
    public static function builder(
        string $siteId,
        string $pluginVersion,
        bool $qaSite,
        ClientInterface $client,
        RequestFactoryInterface $requestFactory
    ): UnleashProjectBuilder {

        // Development builds keep the placeholder in the plugin header.
        if ($pluginVersion === '' || str_contains($pluginVersion, '{VERSION}')) {
            $pluginVersion = '0.0.0-dev';
        }

        return UnleashProject::builder(self::PROJECT)
            ->withHttpClient($client, $requestFactory)
            ->withFreshTtl(12 * HOUR_IN_SECONDS)
            ->withTtlJitter(0.2)
            ->withStaleTtl(7 * DAY_IN_SECONDS)
            ->withMetrics(true, DAY_IN_SECONDS)
            ->withRegistration(RegistrationMode::Off)
            ->withTargetingId($siteId)
            ->withPackageVersion($pluginVersion)
            ->withProperties($qaSite ? ['qaSite' => 'true'] : []);
    }

    /**
     * Registers the project with the library. Without a connection, or with an invalid one,
     * no project is registered and checks return their default.
     */
    public static function connect(
        UnleashProjectBuilder $builder,
        Settings $settings,
        string $siteId
    ): self {

        if ($settings->url === '' && $settings->token === '') {
            return new self($settings, $siteId, null, null);
        }
        if ($settings->url === '' || $settings->token === '') {
            return new self($settings, $siteId, null, 'The URL and the token must be set together.');
        }

        try {
            FeatureFlags::registerProject(
                $builder->withAppUrl($settings->url)->withApiToken($settings->token)->build()
            );
            // The registered-project filter may have replaced it, so the status describes the
            // project that answers the checks.
            $project = FeatureFlags::hasProject(self::PROJECT)
                ? FeatureFlags::getProject(self::PROJECT)
                : null;
        } catch (\Throwable $error) {
            return new self($settings, $siteId, null, $error->getMessage());
        }

        return new self($settings, $siteId, $project, null);
    }

    public function isOn(string $flag, bool $default = false): bool
    {
        return (bool) apply_filters(
            FeatureFlags::HOOK_BASE . '.' . self::PROJECT,
            $default,
            $flag
        );
    }

    /**
     * Whether flags come from Unleash, a stale copy or the defaults, and the last error. For
     * support.
     */
    public function connectionStatus(): ConnectionStatus
    {
        return $this->project instanceof UnleashProject
            ? $this->project->connectionStatus()
            : new ConnectionStatus(ConnectionSource::Defaults);
    }

    /**
     * Whether each flag of the project is on for this site. For support. Fetches the flags if
     * the cached copy is due, like a check would.
     *
     * @return array<string, bool>
     */
    public function states(): array
    {
        $states = [];
        foreach ($this->project?->getFeatures() ?? [] as $feature) {
            // Through the filters, so the state includes overrides by snippets.
            $states[$feature->getName()] = $this->isOn($feature->getName());
        }

        return $states;
    }

    public function settings(): Settings
    {
        return $this->settings;
    }

    /**
     * Identifies the site in Unleash, without being traceable to it.
     */
    public function siteId(): string
    {
        return $this->siteId;
    }

    /**
     * Why the project isn't connected, if the connection settings are invalid.
     */
    public function configurationError(): ?string
    {
        return $this->configurationError;
    }
}
