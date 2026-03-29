<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Notices\Notice\Admin;

use Syde\PayPal\PointOfSale\Notices\Notice\NoticeInterface;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingState;

class GlobalConnectionFailedNotice implements NoticeInterface
{
    /**
     * @var callable
     */
    private $isIntegrationPageCallback;

    private bool $authFailed;

    private string $settingsUrl;

    public function __construct(
        callable $isIntegrationPageCallback,
        bool $authFailed,
        string $settingsUrl
    ) {

        $this->isIntegrationPageCallback = $isIntegrationPageCallback;
        $this->authFailed = $authFailed;
        $this->settingsUrl = $settingsUrl;
    }

    /**
     * @inheritDoc
     */
    public function accepts(string $currentState): bool
    {
        if ($currentState !== OnboardingState::ONBOARDING_COMPLETED) {
            return false;
        }

        if (($this->isIntegrationPageCallback)()) {
            return false;
        }

        return $this->authFailed;
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        ob_start() ?>

        <div class="notice notice-error zettle" style="padding: 1.2rem 1rem">
            <h4 style="margin-top: 0; margin-bottom: .5rem;">
                <?php esc_html_e(
                    'PayPal Point of Sale - Unable to connect to PayPal Point of Sale',
                    'paypal-point-of-sale'
                ); ?>
            </h4>

            <p style="padding-bottom: .5rem;">
                <?php esc_html_e(
                    'Please visit the PayPal Point of Sale integration page to update the API key and connect again.',
                    'paypal-point-of-sale'
                ); ?>
            </p>

            <a class="button button-secondary" href="<?php echo esc_url($this->settingsUrl) ?>">
                <?php esc_html_e('Go to Integration page', 'paypal-point-of-sale') ?>
            </a>
        </div>

        <?php return ob_get_clean();
    }
}
