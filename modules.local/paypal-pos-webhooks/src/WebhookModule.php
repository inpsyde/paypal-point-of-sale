<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

use Exception;
use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderScreenPaymentMethod;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderSideEffects;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use Syde\PayPal\PointOfSale\Webhooks\Rest\Endpoint;
use Syde\PayPal\PointOfSale\Webhooks\Rest\Verifier;
use Throwable;
use WP_CLI;

class WebhookModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    /**
     * @inheritDoc
     */
    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }

    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }

    public function run(ContainerInterface $container): bool
    {
        add_action(
            'init',
            function () use ($container) {
                $this->registerRestRoute($container);
                $this->registerCliCommand($container);
            }
        );

        $sideEffects = $container->get('paypal-pos.webhook.order-side-effects');
        assert($sideEffects instanceof OrderSideEffects);
        $sideEffects->suppressEmails();
        add_action('current_screen', [OrderScreenPaymentMethod::class, 'register']);

        $bootstrap = $container->get('paypal-pos.webhook.bootstrap');
        assert($bootstrap instanceof Bootstrap);

        add_action(
            'paypal-point-of-sale.activate',
            static function () use ($bootstrap) {
                $bootstrap->activate();
            }
        );
        // On update, subscribe existing installs to newly added events. Registration replaces the
        // remote webhook, so it only runs when the stored subscription is missing an event.
        add_action(
            'paypal-point-of-sale.migrate',
            function () use ($bootstrap, $container) {
                if ($this->missingWebhookEvents($container) !== []) {
                    $bootstrap->activate();
                }
            }
        );
        add_action(
            'paypal-point-of-sale.deactivate',
            static function () use ($bootstrap) {
                $bootstrap->deactivate();
            }
        );
        $this->registerRecovery($container);
        $this->registerDelayedJobs($container);
        $this->registerSetupImportChoice();

        $this->registerBackfillTools($container);

        add_filter('wc_order_attribution_origin_label', [self::class, 'filterOriginLabel'], 10, 3);

        return true;
    }

    /**
     * @return string[] Configured events the stored webhook subscription doesn't cover.
     */
    private function missingWebhookEvents(ContainerInterface $container): array
    {
        $configured = (array) $container->get('paypal-pos.webhook.config.events');
        try {
            $storage = $container->get('paypal-pos.webhook.storage');
            assert($storage instanceof WebhookStorageInterface);
            $subscribed = $storage->fetch()->eventNames();
        } catch (Throwable $exception) {
            return $configured;
        }

        return array_values(array_diff($configured, $subscribed));
    }

    /**
     * Show POS orders' Origin as "PayPal POS" instead of "Source: PayPal POS".
     */
    public static function filterOriginLabel(string $label, string $sourceType, string $source): string
    {
        if ($sourceType === PurchaseOrderFactory::ORIGIN_SOURCE_TYPE && $source === PurchaseOrderFactory::ORIGIN_SOURCE) {
            return '';
        }

        return $label;
    }

    private function registerRecovery(ContainerInterface $container): void
    {
        $backfill = static function () use ($container): Backfill\PurchaseBackfill {
            $backfill = $container->get('paypal-pos.webhook.backfill');
            assert($backfill instanceof Backfill\PurchaseBackfill);

            return $backfill;
        };

        // Import what was sold while the plugin was inactive.
        add_action(
            'paypal-point-of-sale.deactivate',
            static function () use ($backfill) {
                $backfill()->rememberDeactivation();
            }
        );
        add_action(
            'paypal-point-of-sale.activate',
            static function () use ($backfill) {
                $backfill()->recoverAfterReactivation();
            }
        );
    }

    private function registerDelayedJobs(ContainerInterface $container): void
    {
        add_action(
            Job\DelayedRetry::HOOK,
            static function (string $type, array $args) use ($container): void {
                $retry = $container->get('paypal-pos.webhook.delayed-retry');
                assert($retry instanceof Job\DelayedRetry);
                $retry->enqueue($type, $args);
            },
            10,
            2
        );
    }

    /**
     * Asks on the last onboarding settings step whether to import past sales once setup completes.
     */
    private function registerSetupImportChoice(): void
    {
        add_action(
            'paypal-pos.onboarding.sync-params.after',
            static function (): void {
                ?>
                <p>
                    <input type="hidden" name="woocommerce_zettle_order_sync_import_on_setup" value="no">
                    <label>
                        <input type="checkbox" name="woocommerce_zettle_order_sync_import_on_setup" value="yes" checked>
                        <?php esc_html_e(
                            'Import past PayPal POS sales as WooCommerce orders when the setup is completed',
                            'paypal-point-of-sale'
                        ); ?>
                    </label>
                </p>
                <?php
            }
        );
    }

    private function registerBackfillTools(ContainerInterface $container): void
    {
        add_filter(
            'woocommerce_debug_tools',
            static function (array $tools) use ($container): array {
                $backfill = $container->get('paypal-pos.webhook.backfill');
                assert($backfill instanceof Backfill\PurchaseBackfill);

                $tools['zettle_import_orders'] = [
                    'name' => __('Import PayPal POS orders', 'paypal-point-of-sale'),
                    'button' => __('Import historical orders', 'paypal-point-of-sale'),
                    'desc' => __(
                        'Fetches all past PayPal POS sales and refunds in the background and creates any that are not yet in WooCommerce.',
                        'paypal-point-of-sale'
                    ) . self::backfillStatus($backfill),
                    'callback' => static function () use ($backfill): string {
                        return self::startMessage($backfill, static fn (): bool => $backfill->start());
                    },
                ];
                $tools['zettle_sync_missed_orders'] = [
                    'name' => __('Sync missed PayPal POS orders', 'paypal-point-of-sale'),
                    'button' => __('Sync missed orders', 'paypal-point-of-sale'),
                    'desc' => __(
                        'Fetches PayPal POS sales and refunds from the last 30 days in the background and creates any that are missing.',
                        'paypal-point-of-sale'
                    ),
                    'callback' => static function () use ($backfill): string {
                        return self::startMessage($backfill, static fn (): bool => $backfill->startMissed());
                    },
                ];

                return $tools;
            }
        );
    }

    /**
     * @param callable(): bool $start
     */
    private static function startMessage(Backfill\PurchaseBackfill $backfill, callable $start): string
    {
        if (!$backfill->isEnabled()) {
            return __('POS order creation is disabled in the PayPal POS settings.', 'paypal-point-of-sale');
        }
        if (!$start()) {
            return __('A PayPal POS import is already running. Try again when it has finished.', 'paypal-point-of-sale');
        }

        return __('PayPal POS sales are being imported in the background.', 'paypal-point-of-sale');
    }

    private static function backfillStatus(Backfill\PurchaseBackfill $backfill): string
    {
        $status = $backfill->status();
        if ($status['state'] === '') {
            return '';
        }

        $summary = sprintf(
            /* translators: 1: run state, 2: number of purchases queued */
            __('Last import: %1$s, %2$d purchases queued.', 'paypal-point-of-sale'),
            $status['state'],
            $status['queued']
        );
        if ($status['error'] !== '') {
            /* translators: %s: error message */
            $summary .= ' ' . sprintf(__('Last error: %s', 'paypal-point-of-sale'), $status['error']);
        }

        return '<br><em>' . esc_html($summary) . '</em>';
    }

    private function registerCliCommand(ContainerInterface $container): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            try {
                WP_CLI::add_command(
                    "zettle webhook",
                    $container->get('paypal-pos.webhook.cli')
                );
            } catch (Exception $exception) {
            }

            return;
        }
    }

    /**
     * Register Listener Webhook Endpoint
     *
     * @param ContainerInterface $container
     */
    private function registerRestRoute(ContainerInterface $container): void
    {
        add_action(
            'rest_api_init',
            static function () use ($container) {
                $namespace = $container->get('paypal-pos.webhook.listener.namespace');
                $route = $container->get('paypal-pos.webhook.listener.route');

                $listenerEndpoint = $container->get('paypal-pos.webhook.listener');
                assert($listenerEndpoint instanceof Endpoint);

                $verifier = $container->get('paypal-pos.webhook.verifier');
                assert($verifier instanceof Verifier);

                register_rest_route(
                    $namespace,
                    $route,
                    [
                        'methods' => implode(',', $listenerEndpoint->methods()),
                        'callback' => [$listenerEndpoint, 'callback'],
                        'permission_callback' => [$verifier, 'verify'],
                    ]
                );
            }
        );
    }
}
