<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\RegistrationMode;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProject;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProjectBuilder;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\SiteId;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Properties\Properties;
use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\ReportItemFactoryInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
// The value of a constant, or else of the environment variable with the same name.
$setting = static function (string $name): ?string {
    if (defined($name)) {
        $value = constant($name);
        return is_bool($value) ? $value ? '1' : '' : (string) $value;
    }
    $value = getenv($name);
    return $value === \false ? null : $value;
};
return [
    /*
     * Name of the project in the flag filters (syde.feature-flags.paypal-pos) and in the
     * option names. The API token selects the Unleash project and environment.
     */
    'paypal-pos.feature-flags.project-name' => static function (): string {
        return 'paypal-pos';
    },
    /*
     * Connection to the production environment of the PayPal POS project in Unleash.
     * Without a URL and token, no project is registered and flag checks return their
     * default.
     */
    'paypal-pos.feature-flags.default-url' => static function (): string {
        return 'https://us.app.unleash-hosted.com/usqq0324/api/';
    },
    'paypal-pos.feature-flags.default-token' => static function (): string {
        return 'paypal-pos:production.v2_D3kWgaKh5XDNV2ZSE7DZcA_bEJIfNkfFOjJW-fowwmM6HZRrCWwDVxJe6SdZat69U0';
    },
    /*
     * Settings for QA, staging and local sites: a constant in wp-config.php, or an
     * environment variable of the same name (e.g. web_environment in .ddev/config.local.yaml).
     */
    'paypal-pos.feature-flags.overridden' => static function () use ($setting): bool {
        return $setting('PAYPAL_POS_FEATURE_FLAGS_URL') !== null || $setting('PAYPAL_POS_FEATURE_FLAGS_TOKEN') !== null;
    },
    // The URL and token are overridden together, so the built-in token never goes to another URL.
    'paypal-pos.feature-flags.url' => static function (C $container) use ($setting): string {
        return $container->get('paypal-pos.feature-flags.overridden') ? $setting('PAYPAL_POS_FEATURE_FLAGS_URL') ?? '' : (string) $container->get('paypal-pos.feature-flags.default-url');
    },
    'paypal-pos.feature-flags.token' => static function (C $container) use ($setting): string {
        return $container->get('paypal-pos.feature-flags.overridden') ? $setting('PAYPAL_POS_FEATURE_FLAGS_TOKEN') ?? '' : (string) $container->get('paypal-pos.feature-flags.default-token');
    },
    'paypal-pos.feature-flags.qa-site' => static function () use ($setting): bool {
        return filter_var($setting('PAYPAL_POS_FEATURE_FLAGS_QA_SITE') ?? \false, \FILTER_VALIDATE_BOOLEAN);
    },
    'paypal-pos.feature-flags.enabled' => static function (C $container): bool {
        return $container->get('paypal-pos.feature-flags.url') !== '' && $container->get('paypal-pos.feature-flags.token') !== '';
    },
    /*
     * Identifies the site in Unleash, without being traceable to it. Also shown in the
     * status report, so support can target a site.
     */
    'paypal-pos.feature-flags.site-id' => static function (): string {
        return SiteId::forCurrentSite();
    },
    'paypal-pos.feature-flags.package-version' => static function (C $container): string {
        $properties = $container->get(Package::PROPERTIES);
        assert($properties instanceof Properties);
        $version = $properties->version();
        // Development builds keep the placeholder in the plugin header.
        return $version === '' || str_contains($version, '{VERSION}') ? '0.0.0-dev' : $version;
    },
    /*
     * Custom context properties, for targeting groups of sites in Unleash. Modules add their
     * properties via extensions.
     */
    'paypal-pos.feature-flags.properties' => static function (): array {
        return [];
    },
    /*
     * Request settings and context. Flags adds the URL and token when it connects.
     */
    'paypal-pos.feature-flags.unleash.builder' => static function (C $container): UnleashProjectBuilder {
        return UnleashProject::builder($container->get('paypal-pos.feature-flags.project-name'))->withHttpClient($container->get('inpsyde.http-client.inner-client'), $container->get('inpsyde.http-client.request-factory'))->withFreshTtl(12 * \HOUR_IN_SECONDS)->withTtlJitter(0.2)->withStaleTtl(7 * \DAY_IN_SECONDS)->withMetrics(\true, \DAY_IN_SECONDS)->withRegistration(RegistrationMode::Off)->withTargetingId($container->get('paypal-pos.feature-flags.site-id'))->withPackageVersion($container->get('paypal-pos.feature-flags.package-version'))->withProperties($container->get('paypal-pos.feature-flags.properties'));
    },
    'paypal-pos.feature-flags.status-report' => static function (C $container): StatusReportItems {
        $factory = $container->get('inpsyde.wc-status-report.item-factory');
        assert($factory instanceof ReportItemFactoryInterface);
        return new StatusReportItems($factory, $container->get('paypal-pos.feature-flags.site-id'), $container->get('paypal-pos.feature-flags.overridden'), $container->get('paypal-pos.feature-flags.qa-site'));
    },
    'paypal-pos.feature-flags' => static function (C $container): Flags {
        $builder = $container->get('paypal-pos.feature-flags.unleash.builder');
        assert($builder instanceof UnleashProjectBuilder);
        $logger = $container->get('paypal-pos.logger');
        assert($logger instanceof LoggerInterface);
        return new Flags($container->get('paypal-pos.feature-flags.project-name'), $container->get('paypal-pos.feature-flags.url'), $container->get('paypal-pos.feature-flags.token'), $builder, $logger);
    },
];
