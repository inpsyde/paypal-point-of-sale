<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Notices;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Inpsyde\StateMachine\StateMachineInterface;
use Syde\PayPal\PointOfSale\Notices\Notice\NoticeDelegator;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as C;

class NoticesModule implements ModuleInterface
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
    public function run(C $container): void
    {
        if (!is_admin()) {
            return;
        }

        $noticeDelegator = $container->get('paypal-pos.notices.notification.delegator');
        assert($noticeDelegator instanceof NoticeDelegator);

        $stateMachine = $container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        $noticeDelegator->delegate(
            $stateMachine->currentState()->name()
        );
    }
}
