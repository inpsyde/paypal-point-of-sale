<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings;

use Syde\Vendor\Zettle\Dhii\Container\ServiceProvider;
use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\BootableProviderAwareTrait;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\BootableProviderModuleInterface;
use Syde\Vendor\Zettle\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
class SettingsModule implements ModuleInterface, BootableProviderModuleInterface
{
    use BootableProviderAwareTrait;
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
        if (!is_admin()) {
            return;
        }
        $this->bootProviders($container, ...$container->get('paypal-pos.settings.provider'));
    }
}
