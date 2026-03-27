<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Queue;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\Queue\Bootstrap;
use Psr\Container\ContainerInterface;

class ZettleQueueModule implements ServiceModule, ExtendingModule, ExecutableModule
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
        if (!$container->has('inpsyde.queue.bootstrap')) {
            return true;
        }

        /** @var Bootstrap $bootstrap */
        $bootstrap = $container->get('inpsyde.queue.bootstrap');

        add_action(
            'paypal-point-of-sale.migrate',
            static function () use ($bootstrap) {
                $bootstrap->activate();
            }
        );
        add_action(
            'paypal-point-of-sale.activate',
            static function () use ($bootstrap) {
                $bootstrap->activate();
            },
            5
        );

        return true;
    }
}
