<?php

//phpcs:disable PSR12.Files.FileHeader.IncorrectOrder
declare (strict_types=1);
/**
 * Plugin Name: PayPal Point of Sale
 * Plugin URI:  https://zettle.inpsyde.com/
 * Description: PayPal Point of Sale Integration for WooCommerce
 * Version: 0.0.0+main.098b707
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 * WC requires at least: 10.2
 * WC tested up to: 10.2
 * Author:      PayPal
 * Author URI:  https://www.paypal.com/us/business/pos
 * License:     GPL-2.0
 * Text Domain: paypal-point-of-sale
 * Domain Path: /languages
 */
/**
 * phpcs:disable PSR1.Files.SideEffects
 * phpcs:disable Squiz.PHP.CommentedOutCode.Found
 */
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation\ValidationFailedException;
(static function () {
    /**
     * Display an error message in the WP admin
     *
     * @param string $message The message content
     *
     * @return void
     */
    function errorNotice(string $message)
    {
        add_action('all_admin_notices', static function () use ($message) {
            $class = 'notice notice-error';
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), wp_kses_post($message));
        });
    }
    $requiresAtLeast = '8.2';
    if (version_compare(\PHP_VERSION, $requiresAtLeast, '<')) {
        errorNotice(sprintf(
            /* translators: required PHP version */
            esc_html__('PayPal Point of Sale requires at least PHP version %s.', 'paypal-point-of-sale'),
            $requiresAtLeast
        ) . '<br>' . sprintf(
            /* translators: required PHP version */
            esc_html__('Please ask your server administrator to update your environment to PHP version %s.', 'paypal-point-of-sale'),
            $requiresAtLeast
        ));
        return;
    }
    if (!class_exists(PluginModule::class) && file_exists(__DIR__ . '/vendor/autoload.php')) {
        include_once __DIR__ . '/vendor/autoload.php';
    }
    function init(): ?Package
    {
        static $initialized;
        static $package;
        if (!$initialized) {
            try {
                $package = (require __DIR__ . '/bootstrap.php')(__FILE__, \true);
            } catch (ValidationFailedException $exc) {
                $messages = array_map(static function ($error): string {
                    if ($error instanceof ValidationFailedException) {
                        return $error->getMessage();
                    }
                    return (string) $error;
                }, $exc->getValidationErrors());
                foreach ($messages as $message) {
                    errorNotice($message);
                }
                return null;
            }
            $initialized = \true;
        }
        return $package;
    }
    add_action('plugins_loaded', static function () {
        $package = init();
        if (!$package) {
            return;
        }
        $container = $package->container();
        // IZET-356, looks like there is no good built-in hook in WP for plugin upgrades
        $version = $container->get('paypal-pos.plugin.properties')->version();
        $versionOptionName = $container->get('paypal-pos.version-option-key');
        if (get_option($versionOptionName) !== $version) {
            do_action('paypal-point-of-sale.migrate');
            update_option($versionOptionName, $version);
        }
    });
    register_activation_hook(__FILE__, static function () {
        init();
        do_action('paypal-point-of-sale.activate');
    });
    register_deactivation_hook(__FILE__, static function () {
        init();
        do_action('paypal-point-of-sale.deactivate');
    });
    add_action('before_woocommerce_init', static function () {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, \true);
        }
    });
})();
