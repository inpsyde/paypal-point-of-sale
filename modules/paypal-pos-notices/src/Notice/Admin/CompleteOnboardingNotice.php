<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices\Notice\Admin;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices\Notice\NoticeInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\OnboardingState;
class CompleteOnboardingNotice implements NoticeInterface
{
    /**
     * @var callable
     */
    private $isPluginsPageCallback;
    private string $settingsUrl;
    public function __construct(callable $isPluginsPageCallback, string $settingsUrl)
    {
        $this->isPluginsPageCallback = $isPluginsPageCallback;
        $this->settingsUrl = $settingsUrl;
    }
    /**
     * @inheritDoc
     */
    public function accepts(string $currentState): bool
    {
        if ($currentState === OnboardingState::ONBOARDING_COMPLETED) {
            return \false;
        }
        return (bool) ($this->isPluginsPageCallback)();
    }
    /**
     * @inheritDoc
     */
    public function render(): string
    {
        ob_start();
        ?>

        <div class="notice zettle" style="padding: 1.2rem 1rem">
            <h4>
                <?php 
        esc_html_e('PayPal Point of Sale Configuration', 'paypal-point-of-sale');
        ?>
            </h4>

            <p>
                <?php 
        esc_html_e('It looks like this is the first time you are using PayPal Point of Sale for WooCommerce.', 'paypal-point-of-sale');
        ?>
            </p>

            <p style="padding-bottom: 1rem;">
                <?php 
        esc_html_e('Please complete the initial configuration in the integration settings.', 'paypal-point-of-sale');
        ?>
            </p>

            <a class="button button-secondary" href="<?php 
        echo esc_url($this->settingsUrl);
        ?>">
                <?php 
        esc_html_e('Take me there!', 'paypal-point-of-sale');
        ?>
            </a>
        </div>

        <?php 
        return (string) ob_get_clean();
    }
}
