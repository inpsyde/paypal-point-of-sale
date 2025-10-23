<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\View;

use Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
use Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonKind;

class UnhandledErrorView implements OnboardingView
{
    use ButtonRendererTrait;

    public function renderHeader(): string
    {
        ob_start() ?>

        <h2>
            <?php esc_html_e('Critical error', 'paypal-point-of-sale') ?>
        </h2>

        <?php return ob_get_clean();
    }

    public function renderContent(): string
    {
        ob_start() ?>

        <p>
            <?php esc_html_e(
                "A critical error occurred.
                Please check the WooCommerce logs for more details
                and press 'Start over' to restart installation.",
                'paypal-point-of-sale'
            ); ?>
        </p>

        <?php return ob_get_clean();
    }

    public function renderProceedButton(): string
    {
        return $this->renderActionButton(
            ButtonAction::DELETE,
            __('Start over', 'paypal-point-of-sale'),
            // do not make it red because the user does not have any other choice
                ['kind' => ButtonKind::PRIMARY]
        );
    }

    public function renderBackButton(): string
    {
        return '';
    }
}
