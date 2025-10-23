<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Exception;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use WP_CLI;

class SyncModule implements ModuleInterface
{

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(
            require __DIR__ . '/../services.php',
            require __DIR__ . '/../extensions.php'
        );
    }

    /**
     * @inheritDoc
     */
    public function run(ContainerInterface $container): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            try {
                WP_CLI::add_command(
                    "zettle sync",
                    $container->get('paypal-pos.sync.cli.sync-product')
                );
                WP_CLI::add_command(
                    "zettle unlink",
                    $container->get('paypal-pos.sync.cli.unlink-product')
                );
                WP_CLI::add_command(
                    "zettle reset",
                    $container->get('paypal-pos.sync.cli.reset')
                );
                WP_CLI::add_command(
                    "zettle export",
                    $container->get('paypal-pos.sync.cli.export')
                );
                WP_CLI::add_command(
                    'zettle exclude',
                    $container->get('paypal-pos.sync.cli.exclude')
                );
            } catch (Exception $exception) {
            }
        }

        $logger = $container->get('paypal-pos.logger');

        // without is_admin it triggers multiple time in ajax requests
        // also to avoid performance issues for users
        if (
            is_admin()
            && $container->get('paypal-pos.sync.price-sync-enabled')
            && !$container->get('paypal-pos.auth.is-failed')
        ) {
            try {
                $settings = $container->get('paypal-pos.settings');

                $storeComparison = $container->get('paypal-pos.onboarding.comparison.store');
                if (!$storeComparison->canSyncPrices()) {
                    $logger->info('Cannot sync prices with PayPal Point of Sale anymore, check your WC settings (currency, country, taxes).');

                    $settings->set('sync_price_strategy', PriceSyncMode::DISABLED);
                }
            } catch (Exception $exception) {
                // likely happens on auth failure when refreshing account data
                $logger->debug('Settings check failed. ' . $exception->getMessage());
            }
        }
    }
}
