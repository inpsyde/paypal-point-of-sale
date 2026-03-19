<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue;

use Syde\Vendor\Zettle\Dhii\Container\ServiceProvider;
use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Bootstrap;
use Syde\Vendor\Zettle\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
class ZettleQueueModule implements ModuleInterface
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
        if (!$container->has('inpsyde.queue.bootstrap')) {
            return;
        }
        /** @var Bootstrap $bootstrap */
        $bootstrap = $container->get('inpsyde.queue.bootstrap');
        add_action('paypal-point-of-sale.migrate', static function () use ($bootstrap) {
            $bootstrap->activate();
        });
        add_action('paypal-point-of-sale.activate', static function () use ($bootstrap) {
            $bootstrap->activate();
        }, 5);
    }
}
