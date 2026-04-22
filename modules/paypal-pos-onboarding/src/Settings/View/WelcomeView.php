<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\View;

class WelcomeView implements OnboardingView
{
    protected array $zettleLink;
    /**
     * @param array $zettleLink
     */
    public function __construct(array $zettleLink)
    {
        $this->zettleLink = $zettleLink;
    }
    public function renderHeader(): string
    {
        ob_start();
        ?>

        <h2>
            <?php 
        esc_html_e('Grow your business with PayPal Point of Sale and WooCommerce', 'paypal-point-of-sale');
        ?>
        </h2>

        <?php 
        return ob_get_clean();
    }
    public function renderContent(): string
    {
        ob_start();
        ?>

        <p>
            <?php 
        esc_html_e('The PayPal Point of Sale point-of-sale system allows you to take cash, card, contactless payments and more.
                 Connect WooCommerce with PayPal Point of Sale to keep products and inventory in sync – all in one place.', 'paypal-point-of-sale');
        ?>
        </p>

        <p>
            <?php 
        esc_html_e('Sync your WooCommerce products and inventory to PayPal Point of Sale in a few clicks.
                Make a sale on either platform and your inventory will update instantly.
                Keep your products up-to-date by managing them solely in WooCommerce,
                so you can focus on selling.', 'paypal-point-of-sale');
        ?>
        </p>

        <p>
            <?php 
        esc_html_e('To see which markets PayPal Point of Sale is available in, please visit ', 'paypal-point-of-sale');
        ?>

            <a class="link"
                rel="noopener noreferrer"
                target="_blank"
                href="<?php 
        echo esc_url_raw($this->zettleLink['url']);
        ?>">
                <?php 
        echo esc_html($this->zettleLink['title']);
        ?></a>.
        </p>

        <div class="zettle-settings-onboarding-content-get-started">
            <h3><?php 
        esc_html_e('How to get started', 'paypal-point-of-sale');
        ?></h3>

            <?php 
        echo wp_kses_post($this->renderGetStartedContent());
        ?>
        </div>

        <?php 
        return ob_get_clean();
    }
    public function renderGetStartedContent(): string
    {
        $imgResources = sprintf('%s/paypal-pos-assets/resources/img', plugin_dir_url(dirname(__DIR__, 4) . '/paypal-point-of-sale.php'));
        ob_start();
        ?>

        <div class="zettle-settings-onboarding-content-get-started-container columns-3">
            <div class="column">
                <img src="<?php 
        echo esc_url_raw("{$imgResources}/connect.jpg");
        ?>"
                    alt="<?php 
        esc_attr_e('Connect in minutes', 'paypal-point-of-sale');
        ?>"
                    title="<?php 
        esc_attr_e('Connect in minutes', 'paypal-point-of-sale');
        ?>">

                <h4><?php 
        esc_html_e('Connect in minutes', 'paypal-point-of-sale');
        ?></h4>
                <p>
                    <?php 
        esc_html_e('Connect your accounts, sync your library to PayPal Point of Sale and start selling.', 'paypal-point-of-sale');
        ?>
                </p>
            </div>
            <div class="column">
                <img src="<?php 
        echo esc_url_raw("{$imgResources}/zettle.jpg");
        ?>"
                    alt="<?php 
        esc_attr_e('Manage products in one place', 'paypal-point-of-sale');
        ?>"
                    title="<?php 
        esc_attr_e('Manage products in one place', 'paypal-point-of-sale');
        ?>">
                <h4><?php 
        esc_html_e('Manage products in one place', 'paypal-point-of-sale');
        ?></h4>
                <p>
                    <?php 
        esc_html_e('Products sync from WooCommerce to PayPal Point of Sale automatically.', 'paypal-point-of-sale');
        ?>
                </p>
            </div>
            <div class="column">
                <img src="<?php 
        echo esc_url_raw("{$imgResources}/sync.jpg");
        ?>"
                    alt="<?php 
        esc_attr_e('Sync in real-time', 'paypal-point-of-sale');
        ?>"
                    title="<?php 
        esc_attr_e('Sync in real-time', 'paypal-point-of-sale');
        ?>">
                <h4><?php 
        esc_html_e('Sync in real-time', 'paypal-point-of-sale');
        ?></h4>
                <p>
                    <?php 
        esc_html_e('Inventory sync both ways when you edit or make a sale.', 'paypal-point-of-sale');
        ?>
                </p>
            </div>
        </div>

        <?php 
        return ob_get_clean();
    }
    public function renderProceedButton(): string
    {
        return '';
    }
    public function renderBackButton(): string
    {
        return '';
    }
}
