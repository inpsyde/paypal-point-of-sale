<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Notices;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\StateMachine\StateMachineInterface;
use Syde\PayPal\PointOfSale\Notices\Notice\NoticeDelegator;
use Psr\Container\ContainerInterface as C;

class NoticesModule implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    /**
     * @inheritDoc
     */
    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }

    /**
     * @inheritDoc
     */
    public function run(C $container): bool
    {
        if (!is_admin()) {
            return true;
        }

        $noticeDelegator = $container->get('paypal-pos.notices.notification.delegator');
        assert($noticeDelegator instanceof NoticeDelegator);

        $stateMachine = $container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        $noticeDelegator->delegate(
            $stateMachine->currentState()->name()
        );

        return true;
    }
}
