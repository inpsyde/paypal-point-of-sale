<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Syde\PayPal\PointOfSale\BootableProviderAwareTrait;
use Syde\PayPal\PointOfSale\BootableProviderModuleInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as C;

class OnboardingModule implements ModuleInterface, BootableProviderModuleInterface
{
    use BootableProviderAwareTrait;

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(
            array_merge(
                require __DIR__ . '/../services.php',
                require __DIR__ . '/../state-machine.php'
            ),
            require __DIR__ . '/../extensions.php'
        );
    }

    /**
     * @inheritDoc
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function run(C $container): void
    {
        $this->bootProviders(
            $container,
            ...$container->get('paypal-pos.onboarding.provider')
        );

        add_filter(
            'woocommerce_settings_api_sanitized_fields_' . $container->get('paypal-pos.settings.wc-integration.id'),
            [$container->get('paypal-pos.onboarding.settings.value-filter.api-key'), 'filterSettingsValues']
        );

        add_action(
            'rest_api_init',
            static function () use ($container) {
                $endpoint = $container->get('paypal-pos.onboarding.disconnect.endpoint');

                register_rest_route(
                    $container->get('paypal-pos.onboarding.rest.namespace'),
                    $endpoint->route(),
                    [
                        'methods' => $endpoint->methods(),
                        'callback' => [$endpoint, 'handleRequest'],
                        'permission_callback' => [$endpoint, 'permissionCallback'],
                        'args' => $endpoint->args(),
                    ]
                );
            }
        );
    }
}
