<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\WcEvents\Hooks\ProductHooks;
use Psr\Container\ContainerInterface;

/**
 * Contains service definitions and bootstrapping logic of this module
 */
class WcEventsModule implements ServiceModule, ExtendingModule, ExecutableModule
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
        $eventDispatcher = $container->get('inpsyde.wc-lifecycle-events.products.hooks');
        assert($eventDispatcher instanceof ProductHooks);
        $eventDispatcher->register();

        return true;
    }
}
