<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class PhpSdkModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }

    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }

    public function run(ContainerInterface $container): bool
    {
        foreach ($container->get('paypal-pos.sdk.provider') as $provider) {
            $provider->boot($container);
        }

        return true;
    }
}
