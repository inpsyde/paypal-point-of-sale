<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Provider;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event\BackButtonPressed;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event\CancelButtonPressed;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event\DeleteButtonPressed;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event\ProceedButtonPressed;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
class StateMachineProvider implements Provider
{
    /**
     * @var StateMachineInterface
     */
    private $stateMachine;
    /**
     * StateMachineProvider constructor.
     *
     * @param StateMachineInterface $stateMachine
     */
    public function __construct(StateMachineInterface $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }
    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_action('woocommerce_init', function () {
            if (!is_admin()) {
                return;
            }
            $state = filter_input(\INPUT_POST, 'zettle_onboarding_state');
            if (!$state) {
                return;
            }
            switch (\true) {
                case filter_input(\INPUT_POST, ButtonAction::PROCEED):
                    $this->stateMachine->handle(ProceedButtonPressed::fromGlobals());
                    break;
                case filter_input(\INPUT_POST, ButtonAction::BACK):
                    $this->stateMachine->handle(BackButtonPressed::fromGlobals());
                    break;
                case filter_input(\INPUT_POST, ButtonAction::CANCEL):
                    $this->stateMachine->handle(CancelButtonPressed::fromGlobals());
                    break;
                case filter_input(\INPUT_POST, ButtonAction::DELETE):
                    $this->stateMachine->handle(DeleteButtonPressed::fromGlobals());
                    break;
            }
        });
        return \true;
    }
}
