<?php

declare(strict_types=1);

/**
 * Bootstrap for the PayPal POS "real WP" integration tests.
 *
 * This sub-project has composer type "project" (not "wordpress-plugin"), so WLITH's
 * PackageTypeDetector returns PackageType::Other and the default Setup bundle skips
 * the automatic symlink + activate tasks. We perform those steps ourselves below
 * against the real plugin directory two levels up.
 */

use Syde\PocWpLiteIntegrationTestHelper\Bootstrap;
use Syde\PocWpLiteIntegrationTestHelper\BootstrapLifecycle;
use Syde\PocWpLiteIntegrationTestHelper\Container\ServiceLocator;
use Syde\PocWpLiteIntegrationTestHelper\Path\WordPressPath;
use Syde\PocWpLiteIntegrationTestHelper\WpTestEnv;
use Symfony\Component\Filesystem\Filesystem;

$subprojectPath = __DIR__;
$vendorPath = "{$subprojectPath}/vendor";
$pluginPath = dirname($subprojectPath, 2);
$pluginSlug = 'paypal-point-of-sale';

if (!is_dir($vendorPath)) {
    fwrite(
        STDERR,
        "Run `composer install` inside tests/wp-integration before running the suite.\n"
    );
    exit(1);
}

/** @var \Composer\Autoload\ClassLoader $subprojectLoader */
$subprojectLoader = require "{$vendorPath}/autoload.php";

$envFile = "{$subprojectPath}/.env.phpunit";
if (is_file($envFile)) {
    \Dotenv\Dotenv::createImmutable($subprojectPath, '.env.phpunit')->load();
}

$filesystem = new Filesystem();

$symlinkPlugin = static function () use ($filesystem, $pluginPath, $pluginSlug): void {
    $wpPath = ServiceLocator::retrieve(WordPressPath::class)->path();
    $target = "{$wpPath}/wp-content/plugins/{$pluginSlug}";
    if (!$filesystem->exists($target)) {
        $filesystem->symlink($pluginPath, $target);
    }
};

$unlinkPlugin = static function () use ($filesystem, $pluginSlug): void {
    $wpPath = ServiceLocator::retrieve(WordPressPath::class)->path();
    $filesystem->remove("{$wpPath}/wp-content/plugins/{$pluginSlug}");
};

$muPluginFile = static function (): string {
    return ServiceLocator::retrieve(WordPressPath::class)->path()
        . '/wp-content/mu-plugins/paypal-pos-test-credentials.php';
};

$installTestCredentialsMuPlugin = static function () use ($filesystem, $muPluginFile): void {
    $filesystem->dumpFile($muPluginFile(), <<<'PHP'
        <?php
        /**
         * Bridges IZETTLE_API_KEY / IZETTLE_CLIENT_ID env vars into the plugin's
         * credentials service, which normally reads from WP options populated via the onboarding flow.
         */
        add_action(
            'inpsyde.modularity.paypal-point-of-sale.init',
            static function ($package): void {
                $module = new class implements
                    \Inpsyde\Modularity\Module\Module,
                    \Inpsyde\Modularity\Module\ExtendingModule
                {
                    public function id(): string
                    {
                        return 'paypal-pos-test-credentials';
                    }

                    public function extensions(): array
                    {
                        return [
                            'paypal-pos.oauth.credentials.parent' => static function () {
                                return new class implements \Psr\Container\ContainerInterface {
                                    private array $envMap = [
                                        'api_key' => 'IZETTLE_API_KEY',
                                        'client_id' => 'IZETTLE_CLIENT_ID',
                                    ];

                                    public function get(string $id): string
                                    {
                                        if (!$this->has($id)) {
                                            throw new class extends \Exception implements
                                                \Psr\Container\NotFoundExceptionInterface {};
                                        }
                                        return (string) (getenv($this->envMap[$id]) ?: '');
                                    }

                                    public function has(string $id): bool
                                    {
                                        return isset($this->envMap[$id]);
                                    }
                                };
                            },
                            // Add an error_log-backed PSR-3 logger to the CompoundLogger so every
                            // plugin log message appears in wp-content/paypal-pos.log.
                            'paypal-pos.logger' => static function ($compound) {
                                $compound->addLogger(new class extends \Psr\Log\AbstractLogger {
                                    public function log($level, $message, array $context = []): void
                                    {
                                        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
                                        file_put_contents(
                                            WP_CONTENT_DIR . '/paypal-pos.log',
                                            sprintf("[%s] [%s] %s%s\n", date('H:i:s'), $level, $message, $ctx),
                                            FILE_APPEND
                                        );
                                    }
                                });
                                return $compound;
                            },
                        ];
                    }
                };

                $package->addModule($module);
            }
        );
        PHP);
};

$removeTestCredentialsMuPlugin = static function () use ($filesystem, $muPluginFile): void {
    $filesystem->remove($muPluginFile());
};

Bootstrap::init(
    $subprojectPath,
    new BootstrapLifecycle(
        setup: static function () use ($symlinkPlugin, $installTestCredentialsMuPlugin, $pluginSlug): void {
            WpTestEnv::setup();
            $symlinkPlugin();
            $installTestCredentialsMuPlugin();

            // Truncate plugin log so runs start clean.
            file_put_contents(
                ServiceLocator::retrieve(WordPressPath::class)->path() . '/wp-content/paypal-pos.log',
                ''
            );

            // SAVEQUERIES floods debug.log with every SQL statement, hiding plugin errors
            // we actually care about. WLITH currently enables it in the setup.
            WpTestEnv::runWpCliCommand(['config', 'set', 'SAVEQUERIES', 'false', '--raw']);

            WpTestEnv::runWpCliCommand(['plugin', 'activate', 'woocommerce', '--network']);
            WpTestEnv::runWpCliCommand(['plugin', 'activate', $pluginSlug, '--network']);

            WpTestEnv::runWpCliCommand(['option', 'update', 'woocommerce_currency', 'GBP']);
            WpTestEnv::runWpCliCommand(['option', 'update', 'woocommerce_default_country', 'GB']);
        },
        load: static function () use ($subprojectLoader): void {
            WpTestEnv::load();

            /*
             * When wp-load.php runs, the main plugin includes its own vendor/autoload.php
             * which prepends a second Composer ClassLoader to the SPL stack. That loader
             * maps `PHPUnit\Framework\TestSuite` to the main project's PHPUnit 9 copy
             * (no `empty()` method), which then breaks PHPUnit 10's TestSuiteMapper.
             * Re-prepend the subproject loader so PHPUnit's own classes resolve here.
             */
            $subprojectLoader->unregister();
            $subprojectLoader->register(true);
        },
        cleanup: static function () use ($unlinkPlugin, $removeTestCredentialsMuPlugin): void {
            $removeTestCredentialsMuPlugin();
            $unlinkPlugin();
            WpTestEnv::cleanup();
        },
    ),
);
