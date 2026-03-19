<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks;

use Syde\Vendor\Zettle\Dhii\Container\ServiceProvider;
use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Rest\Endpoint;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Rest\Verifier;
use Syde\Vendor\Zettle\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\WP_CLI;
class WebhookModule implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(require __DIR__ . '/../services.php', require __DIR__ . '/../extensions.php');
    }
    /**
     * @inheritDoc
     */
    public function run(ContainerInterface $container): void
    {
        add_action('init', function () use ($container) {
            $this->registerRestRoute($container);
            $this->registerCliCommand($container);
        });
        $bootstrap = $container->get('paypal-pos.webhook.bootstrap');
        assert($bootstrap instanceof Bootstrap);
        add_action('paypal-point-of-sale.activate', static function () use ($bootstrap) {
            $bootstrap->activate();
        });
        add_action('paypal-point-of-sale.deactivate', static function () use ($bootstrap) {
            $bootstrap->deactivate();
        });
    }
    private function registerCliCommand(ContainerInterface $container)
    {
        if (defined('Syde\Vendor\Zettle\WP_CLI') && WP_CLI) {
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
    private function registerRestRoute(ContainerInterface $container)
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
