<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Provider;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event\BackButtonPressed;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event\CancelButtonPressed;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event\DeleteButtonPressed;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event\ProceedButtonPressed;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
class StateMachineProvider implements Provider
{
    private StateMachineInterface $stateMachine;
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
        add_action('woocommerce_init', function (): void {
            if (!is_admin()) {
                return;
            }
            $state = filter_input(\INPUT_POST, 'zettle_onboarding_state', \FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            if (!$state) {
                return;
            }
            switch (\true) {
                case filter_input(\INPUT_POST, ButtonAction::PROCEED, \FILTER_SANITIZE_FULL_SPECIAL_CHARS):
                    $this->stateMachine->handle(ProceedButtonPressed::fromGlobals());
                    break;
                case filter_input(\INPUT_POST, ButtonAction::BACK, \FILTER_SANITIZE_FULL_SPECIAL_CHARS):
                    $this->stateMachine->handle(BackButtonPressed::fromGlobals());
                    break;
                case filter_input(\INPUT_POST, ButtonAction::CANCEL, \FILTER_SANITIZE_FULL_SPECIAL_CHARS):
                    $this->stateMachine->handle(CancelButtonPressed::fromGlobals());
                    break;
                case filter_input(\INPUT_POST, ButtonAction::DELETE, \FILTER_SANITIZE_FULL_SPECIAL_CHARS):
                    $this->stateMachine->handle(DeleteButtonPressed::fromGlobals());
                    break;
            }
        });
        return \true;
    }
}
