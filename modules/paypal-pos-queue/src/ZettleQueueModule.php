<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Inpsyde\Queue\Bootstrap;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
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
            return \true;
        }
        /** @var Bootstrap $bootstrap */
        $bootstrap = $container->get('inpsyde.queue.bootstrap');
        // On every request, not just on migrate: while up to date it's one autoloaded option read.
        $bootstrap->upgrade($container->get('inpsyde.queue.schema.option'));
        add_action('paypal-point-of-sale.activate', static function () use ($bootstrap) {
            $bootstrap->activate();
        }, 5);
        return \true;
    }
}
