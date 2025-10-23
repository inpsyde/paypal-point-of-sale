<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Notices\Notice\Admin;

use Syde\PayPal\PointOfSale\Notices\Notice\NoticeInterface;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingState;

class IntegrationConnectionFailedNotice implements NoticeInterface
{
    /**
     * @var callable
     */
    private $isIntegrationPageCallback;

    /**
     * @var callable
     */
    private $authCheckCallback;

    /**
     * @var bool
     */
    private $isSavingSettings;

    /**
     * @var string
     */
    private $apiCreationLink;

    public function __construct(
        callable $isIntegrationPageCallback,
        callable $authCheckCallback,
        bool $isSavingSettings,
        string $apiCreationLink
    ) {

        $this->isIntegrationPageCallback = $isIntegrationPageCallback;
        $this->authCheckCallback = $authCheckCallback;
        $this->isSavingSettings = $isSavingSettings;
        $this->apiCreationLink = $apiCreationLink;
    }

    /**
     * @inheritDoc
     */
    public function accepts(string $currentState): bool
    {
        if ($currentState !== OnboardingState::ONBOARDING_COMPLETED) {
            return false;
        }

        if (!($this->isIntegrationPageCallback)()) {
            return false;
        }

        // admin_notices fires before WC settings save handling
        // and WC also does not redirect to GET after saving settings.
        // So if we check auth in this request, we may use old API key.
        if ($this->isSavingSettings) {
            return false;
        }

        if (($this->authCheckCallback)()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        ob_start() ?>

        <div class="notice notice-error zettle" style="padding: 1.2rem 1rem">
            <h4 style="margin-top: 0">
                <?php esc_html_e(
                    'PayPal Point of Sale - Unable to connect to PayPal Point of Sale',
                    'paypal-point-of-sale'
                ); ?>
            </h4>

            <p>
                <?php esc_html_e(
                    'Create a new API key at PayPal Point of Sale and paste it in the field below to connect again.',
                    'paypal-point-of-sale'
                ); ?>
            </p>

            <p style="padding-bottom: 1rem;">
                <?php esc_html_e(
                    'If the connection problem still occur, please disconnect this integration and reconnect again.',
                    'paypal-point-of-sale'
                ); ?>
            </p>

            <a class="button button-secondary" href="<?php echo esc_url($this->apiCreationLink) ?>">
                <?php esc_html_e('Create PayPal Point of Sale API key', 'paypal-point-of-sale') ?>
            </a>
        </div>

        <?php return ob_get_clean();
    }
}
