<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\WcEvents;

use Syde\Vendor\Zettle\Dhii\Container\ServiceProvider;
use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\Hooks\ProductHooks;
use Syde\Vendor\Zettle\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
/**
 * Contains service definitions and bootstrapping logic of this module
 */
class WcEventsModule implements ModuleInterface
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
        $eventDispatcher = $container->get('inpsyde.wc-lifecycle-events.products.hooks');
        assert($eventDispatcher instanceof ProductHooks);
        $eventDispatcher->register();
    }
}
