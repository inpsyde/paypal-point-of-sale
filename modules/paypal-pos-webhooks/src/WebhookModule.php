<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks;

use Exception;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Rest\Endpoint;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Rest\Verifier;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\WP_CLI;
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
        $bootstrap = $container->get('paypal-pos.webhook.bootstrap');
        assert($bootstrap instanceof Bootstrap);
        add_action('paypal-point-of-sale.activate', static function () use ($bootstrap) {
            $bootstrap->activate();
        });
        add_action('paypal-point-of-sale.deactivate', static function () use ($bootstrap) {
            $bootstrap->deactivate();
        });
        return \true;
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
