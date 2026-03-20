<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\WcEvents\Hooks\ProductHooks;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;

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
