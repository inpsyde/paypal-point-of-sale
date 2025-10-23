<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Settings;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Syde\PayPal\PointOfSale\BootableProviderAwareTrait;
use Syde\PayPal\PointOfSale\BootableProviderModuleInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class SettingsModule implements ModuleInterface, BootableProviderModuleInterface
{
    use BootableProviderAwareTrait;

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
        if (!is_admin()) {
            return;
        }

        $this->bootProviders(
            $container,
            ...$container->get('paypal-pos.settings.provider')
        );
    }
}
