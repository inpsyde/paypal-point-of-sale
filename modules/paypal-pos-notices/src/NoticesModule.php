<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices;

use Syde\Vendor\Zettle\Dhii\Container\ServiceProvider;
use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices\Notice\NoticeDelegator;
use Syde\Vendor\Zettle\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
class NoticesModule implements ModuleInterface
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
    public function run(C $container): void
    {
        if (!is_admin()) {
            return;
        }
        $noticeDelegator = $container->get('paypal-pos.notices.notification.delegator');
        assert($noticeDelegator instanceof NoticeDelegator);
        $stateMachine = $container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);
        $noticeDelegator->delegate($stateMachine->currentState()->name());
    }
}
