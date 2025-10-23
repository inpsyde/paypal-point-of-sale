<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Inpsyde\WcEvents\Hooks\ProductHooks;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

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
        $eventDispatcher = $container->get('inpsyde.wc-lifecycle-events.products.hooks');
        assert($eventDispatcher instanceof ProductHooks);
        $eventDispatcher->register();
    }
}
