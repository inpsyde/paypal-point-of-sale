<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\RegistrationMode;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProject;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProjectBuilder;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\FeatureFlags;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\SiteId;
use Syde\Vendor\Zettle\Inpsyde\Wp\HttpClient\Client as WpHttpClient;
use Syde\Vendor\Zettle\Nyholm\Psr7\Factory\Psr17Factory;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestFactoryInterface;
/**
 * The PayPal POS feature flags. The project is registered before the modules are loaded, so
 * flags can decide which modules are loaded, and checks work from any code, including other
 * modules' service definitions.
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
    private function __construct(private readonly Settings $settings, private readonly string $siteId, private readonly ?string $configurationError)
    {
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
            return new self($settings, $siteId, $error->getMessage());
        }
        return self::connect($builder, $settings, $siteId);
    }
    /**
     * The request settings and the context, without the connection. They keep each site
     * within its share of the Unleash request budget.
     */
    public static function builder(string $siteId, string $pluginVersion, bool $qaSite, ClientInterface $client, RequestFactoryInterface $requestFactory): UnleashProjectBuilder
    {
        // Development builds keep the placeholder in the plugin header.
        if ($pluginVersion === '' || str_contains($pluginVersion, '{VERSION}')) {
            $pluginVersion = '0.0.0-dev';
        }
        return UnleashProject::builder(self::PROJECT)->withHttpClient($client, $requestFactory)->withFreshTtl(12 * \HOUR_IN_SECONDS)->withTtlJitter(0.2)->withStaleTtl(7 * \DAY_IN_SECONDS)->withMetrics(\true, \DAY_IN_SECONDS)->withRegistration(RegistrationMode::Off)->withTargetingId($siteId)->withPackageVersion($pluginVersion)->withProperties($qaSite ? ['qaSite' => 'true'] : []);
    }
    /**
     * Registers the project with the library. Without a connection, or with an invalid one,
     * no project is registered and checks return their default.
     */
    public static function connect(UnleashProjectBuilder $builder, Settings $settings, string $siteId): self
    {
        if ($settings->url === '' && $settings->token === '') {
            return new self($settings, $siteId, null);
        }
        if ($settings->url === '' || $settings->token === '') {
            return new self($settings, $siteId, 'The URL and the token must be set together.');
        }
        try {
            FeatureFlags::registerProject($builder->withAppUrl($settings->url)->withApiToken($settings->token)->build());
        } catch (\Throwable $error) {
            return new self($settings, $siteId, $error->getMessage());
        }
        return new self($settings, $siteId, null);
    }
    public function isOn(string $flag, bool $default = \false): bool
    {
        return (bool) apply_filters(FeatureFlags::HOOK_BASE . '.' . self::PROJECT, $default, $flag);
    }
    /**
     * Which modules to load: a module listed as class name => flag is loaded only while the
     * flag is on, the others always.
     *
     * @param array<int|class-string, class-string|string> $modules
     * @param bool $all Loads every module regardless of its flag, e.g. so uninstalling cleans
     *     up after modules that are off.
     * @return list<class-string>
     */
    public function modules(array $modules, bool $all = \false): array
    {
        $classNames = [];
        foreach ($modules as $key => $value) {
            if (is_int($key)) {
                $classNames[] = $value;
                continue;
            }
            if ($all || $this->isOn($value)) {
                $classNames[] = $key;
            }
        }
        /** @var list<class-string> */
        return $classNames;
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
