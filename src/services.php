<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Container\WpOptionContainer;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Container\WritableContainerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Http\PageReloader;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Http\PageReloaderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Logging\Logger\CompoundLogger;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation\CompositeValidator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation\RequiredExtensionsValidator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation\RequiredPluginsValidator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation\ValidatorInterface;
use UnexpectedValueException;
use WC_Tax;
return ['paypal-pos.is-debug' => static function (C $container): bool {
    return defined('WP_DEBUG') && \WP_DEBUG;
}, 'paypal-pos.throw-unhandled-errors' => static function (C $container): bool {
    $envValue = getenv('IZETTLE_THROW_UNHANDLED_ERRORS');
    if ($envValue === \false || $envValue === '') {
        // not specified
        return $container->get('paypal-pos.is-debug');
    }
    return $envValue === '1';
}, 'paypal-pos.init-possible' => static function (C $container): bool {
    /**
     * The onboarding module will extend this according to the onboarding state
     */
    return \false;
}, 'paypal-pos.requirements.validator' => static function (C $container): ValidatorInterface {
    /** @psalm-suppress PossiblyInvalidArgument */
    return new CompositeValidator([$container->get('paypal-pos.requirements.plugins.validator'), $container->get('paypal-pos.requirements.extensions.validator')]);
}, 'paypal-pos.requirements.plugins.validator' => static function (C $container): ValidatorInterface {
    return new RequiredPluginsValidator($container->get('paypal-pos.requirements.plugins'));
}, 'paypal-pos.requirements.plugins' => static function (): array {
    return ['woocommerce/woocommerce.php' => 'WooCommerce'];
}, 'paypal-pos.requirements.extensions.validator' => static function (C $container): ValidatorInterface {
    return new RequiredExtensionsValidator($container->get('paypal-pos.requirements.extensions'));
}, 'paypal-pos.requirements.extensions' => static function (): array {
    return ['mb_strtolower' => 'mbstring', 'json_encode' => 'json', 'openssl_get_cipher_methods' => 'openssl'];
}, 'paypal-pos.is-multisite' => static function (): bool {
    /** @psalm-suppress RedundantCastGivenDocblockType */
    return (bool) is_multisite();
}, 'paypal-pos.current-site-id' => static function (): int {
    /** @psalm-suppress RedundantCastGivenDocblockType */
    return (int) get_current_blog_id();
}, 'paypal-pos.wc.shop.location' => static function (): array {
    /** @psalm-suppress RedundantCastGivenDocblockType */
    return (array) wc_get_base_location();
}, 'paypal-pos.wc.tax.standard-rates' => static function (C $container): array {
    return WC_Tax::find_rates($container->get('paypal-pos.wc.shop.location'));
}, 'paypal-pos.temp-dir' => static function (): string {
    /** @psalm-suppress RedundantCastGivenDocblockType */
    return (string) get_temp_dir();
}, 'inpsyde.assets.registry' => static function (C $container): array {
    return [];
}, 'inpsyde.metabox.registry' => static function (C $container): array {
    return [];
}, 'paypal-pos.http.page-reloader' => static function (C $container): PageReloaderInterface {
    return new PageReloader();
}, 'paypal-pos.settings' => static function (): WritableContainerInterface {
    return new WpOptionContainer('woocommerce_zettle_settings');
}, 'paypal-pos.setup-info' => static function (): WritableContainerInterface {
    return new WpOptionContainer('woocommerce_zettle_info');
}, 'paypal-pos.sdk.integration-id.container' => static function (C $container): WritableContainerInterface {
    return $container->get('paypal-pos.settings');
}, 'paypal-pos.oauth.token-storage.container' => static function (C $container): WritableContainerInterface {
    return $container->get('paypal-pos.settings');
}, 'paypal-pos.webhook.storage.container' => static function (C $container): WritableContainerInterface {
    return $container->get('paypal-pos.settings');
}, 'paypal-pos.logger' => static function (C $container): CompoundLogger {
    return new CompoundLogger($container->get('paypal-pos.logger.woocommerce'), $container->get('paypal-pos.logger.wonolog'));
}, 'paypal-pos.plugin.properties' => static function (): PluginProperties {
    return new PluginProperties(__DIR__ . '/../paypal-point-of-sale.php');
}, 'paypal-pos.version-option-key' => static function (): string {
    return 'zettle_pos_integration_version';
}, 'paypal-pos.clear-cache' => static function (C $container): callable {
    $orgTransientKey = $container->get('paypal-pos.sdk.dal.provider.organization.transient-key');
    return static function () use ($orgTransientKey): void {
        delete_transient($orgTransientKey);
    };
}, 'paypal-pos.wp.date-format' => static function (C $container): string {
    return get_option('date_format');
}, 'paypal-pos.wp.time-format' => static function (C $container): string {
    return get_option('time_format');
}, 'paypal-pos.date-time-format' => static function (C $container): string {
    return $container->get('paypal-pos.wp.time-format') . ' ' . $container->get('paypal-pos.wp.date-format');
}, 'paypal-pos.format-timestamp' => static function (C $container): callable {
    $format = $container->get('paypal-pos.date-time-format');
    return static function (int $timestamp) use ($format): string {
        if (!is_string($date = wp_date($format, $timestamp))) {
            throw new UnexpectedValueException(sprintf('Cannot get date with format "%1$s" for timestamp "%2$s"', $format, $timestamp));
        }
        return $date;
    };
}, 'inpsyde.wc-status-report.plugin.name' => static function (C $container): string {
    $plugin = $container->get('paypal-pos.plugin.properties');
    return $plugin->name();
}];
