<?php

declare(strict_types=1);

/**
 * Bootstrap for the PayPal POS "real WP" integration tests.
 */

use Syde\PocWpLiteIntegrationTestHelper\Bootstrap;
use Syde\PocWpLiteIntegrationTestHelper\BootstrapLifecycle;
use Syde\PocWpLiteIntegrationTestHelper\Container\ServiceLocator;
use Syde\PocWpLiteIntegrationTestHelper\Path\WordPressPath;
use Syde\PocWpLiteIntegrationTestHelper\Task\ActivateTestedPlugin;
use Syde\PocWpLiteIntegrationTestHelper\WpTestEnv;
use Symfony\Component\Filesystem\Filesystem;

$pluginPath = dirname(__DIR__, 2);
$vendorPath = "{$pluginPath}/vendor";

$envFile = "{$pluginPath}/.env.phpunit";

if (is_file($envFile)) {
    // Loading env files could be handled by the WPLITH automatically
    \Dotenv\Dotenv::createImmutable($pluginPath, '.env.phpunit')->load();
}

Bootstrap::init(
    $pluginPath,
    bootstrapSequence: new BootstrapLifecycle(
        setup: static function () use ($pluginPath) : void {
            WpTestEnv::setup();
            // Copying or writing on-demand mu-plugins could be handled by WPLITH
            (new Filesystem())->copy(
                __DIR__ . '/mu-plugins/paypal-pos-test-credentials.php',
                ServiceLocator::retrieve(WordPressPath::class)->path() . '/wp-content/mu-plugins/paypal-pos-test-credentials.php'
            );

            // Truncate plugin log so runs start clean.
            file_put_contents(
                ServiceLocator::retrieve(WordPressPath::class)->path() . '/wp-content/paypal-pos.log',
                ''
            );

            // SAVEQUERIES floods debug.log with every SQL statement, hiding plugin errors
            // we actually care about. WLITH currently enables it in the setup.
            WpTestEnv::runWpCliCommand(['config', 'set', 'SAVEQUERIES', 'false', '--raw']);

            WpTestEnv::runWpCliCommand(['plugin', 'activate', 'woocommerce', '--network']);
            // WC has to be activated before as it's a requirement of the plugin
            ServiceLocator::retrieve(ActivateTestedPlugin::class)->execute();

            WpTestEnv::runWpCliCommand(['option', 'update', 'woocommerce_currency', 'GBP']);
            WpTestEnv::runWpCliCommand(['option', 'update', 'woocommerce_default_country', 'GB']);
        },
        cleanup: static function (): void {
            // Removing these mu-plugins could be handled by WPLITH
            (new Filesystem())->remove(
                ServiceLocator::retrieve(WordPressPath::class)->path() . '/wp-content/mu-plugins/paypal-pos-test-credentials.php'
            );
            WpTestEnv::cleanup();
        },
    ),
);

require "{$vendorPath}/autoload.php";
