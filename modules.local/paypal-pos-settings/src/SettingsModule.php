<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Settings;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class SettingsModule implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }

    public function run(ContainerInterface $container): bool
    {
        if (!is_admin()) {
            return true;
        }

        foreach ($container->get('paypal-pos.settings.provider') as $provider) {
            $provider->boot($container);
        }

        return true;
    }
}
