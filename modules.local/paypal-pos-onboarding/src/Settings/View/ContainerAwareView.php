<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\View;

use Inpsyde\StateMachine\StateMachineInterface;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingState;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;

class ContainerAwareView implements OnboardingView
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var OnboardingView
     */
    private $view;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function renderHeader(): string
    {
        return $this->view()->renderHeader();
    }

    public function renderContent(): string
    {
        return $this->view()->renderContent();
    }

    public function renderProceedButton(): string
    {
        return $this->view()->renderProceedButton();
    }

    public function renderBackButton(): string
    {
        return $this->view()->renderBackButton();
    }

    /**
     * @return OnboardingView
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
     * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
     */
    private function view(): OnboardingView
    {
        if ($this->view instanceof OnboardingView) {
            return $this->view;
        }

        $stateMachine = $this->container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        switch ($stateMachine->currentState()->name()) {
            case OnboardingState::WELCOME:
                $this->view = new WelcomeView(
                    $this->container->get('paypal-pos.onboarding.zettle-link')
                );
                break;
            case OnboardingState::API_CREDENTIALS:
                $this->view = new ApiCredentialsView(
                    $this->container->get('paypal-pos.settings.wc-integration'),
                    $this->container->get('paypal-pos.settings.account.link.api-key-creation'),
                    __('Authorise connection', 'paypal-point-of-sale'),
                    __(
                        'Please paste the API key in the field below.',
                        'paypal-point-of-sale'
                    ),
                    [
                        'api_key',
                    ]
                );
                break;
            case OnboardingState::INVALID_CREDENTIALS:
                $this->view = (new SimpleView(
                    __('Authentication failed', 'paypal-point-of-sale'),
                    __(
                        "We could not authenticate with the credentials you provided.
                        Press 'Start over' to re-enter your credentials.
                        ",
                        'paypal-point-of-sale'
                    )
                ))
                    ->withProceedButton(__('Start over', 'paypal-point-of-sale'));
                break;
            case OnboardingState::SYNC_PARAM_VAT:
                $this->view = $this->container->get('paypal-pos.onboarding.settings.view.sync-vat-param');
                break;
            case OnboardingState::SYNC_PARAM_PRODUCTS:
                $this->view = $this->container->get('paypal-pos.onboarding.settings.view.product-sync-params');
                break;
            case OnboardingState::SYNC_PROGRESS:
                $this->view = $this->container->get('paypal-pos.onboarding.settings.view.sync-progress');
                break;
            case OnboardingState::SYNC_FINISHED:
                $this->view = new SyncFinishedView(
                    $this->container->get('paypal-pos.onboarding.zettle-products-link')
                );
                break;
            case OnboardingState::ONBOARDING_COMPLETED:
                $this->view = $this->container->get('paypal-pos.onboarding.settings.view.onboarding-completed');
                break;
            case OnboardingState::UNHANDLED_ERROR:
                $this->view = new UnhandledErrorView();
                break;
            default:
                $this->view = (new SimpleView(
                    sprintf(
                        '%s__WHOOPS__',
                        $stateMachine->currentState()->name()
                    ),
                    ''
                ))
                    ->withBackButton();
        }

        return $this->view;
    }
}
