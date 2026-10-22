<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks;

use Exception;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order\OrderSideEffects;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Rest\Endpoint;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Rest\Verifier;
use Throwable;
use WP_CLI;
class WebhookModule implements ServiceModule, ExecutableModule
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
    public function run(ContainerInterface $container): bool
    {
        add_action('init', function () use ($container) {
            $this->registerRestRoute($container);
            $this->registerCliCommand($container);
        });
        $sideEffects = $container->get('paypal-pos.webhook.order-side-effects');
        assert($sideEffects instanceof OrderSideEffects);
        $sideEffects->suppressEmails();
        $bootstrap = $container->get('paypal-pos.webhook.bootstrap');
        assert($bootstrap instanceof Bootstrap);
        add_action('paypal-point-of-sale.activate', static function () use ($bootstrap) {
            $bootstrap->activate();
        });
        // On update, subscribe existing installs to newly added events. Registration replaces the
        // remote webhook, so it only runs when the stored subscription is missing an event.
        add_action('paypal-point-of-sale.migrate', function () use ($bootstrap, $container) {
            if ($this->missingWebhookEvents($container) !== []) {
                $bootstrap->activate();
            }
        });
        add_action('paypal-point-of-sale.deactivate', static function () use ($bootstrap) {
            $bootstrap->deactivate();
        });
        return \true;
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
    private function registerCliCommand(ContainerInterface $container): void
    {
        if (defined('WP_CLI') && \WP_CLI) {
            try {
                WP_CLI::add_command("zettle webhook", $container->get('paypal-pos.webhook.cli'));
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
        add_action('rest_api_init', static function () use ($container) {
            $namespace = $container->get('paypal-pos.webhook.listener.namespace');
            $route = $container->get('paypal-pos.webhook.listener.route');
            $listenerEndpoint = $container->get('paypal-pos.webhook.listener');
            assert($listenerEndpoint instanceof Endpoint);
            $verifier = $container->get('paypal-pos.webhook.verifier');
            assert($verifier instanceof Verifier);
            register_rest_route($namespace, $route, ['methods' => implode(',', $listenerEndpoint->methods()), 'callback' => [$listenerEndpoint, 'callback'], 'permission_callback' => [$verifier, 'verify']]);
        });
    }
}
