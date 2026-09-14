<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\View;

class OnboardingCompletedView implements OnboardingView
{
    use ButtonRendererTrait;
    private array $zettleProductsLink;
    private array $settingsLink;
    /**
     * @param array $zettleProductsLink
     */
    public function __construct(array $zettleProductsLink, array $settingsLink)
    {
        $this->zettleProductsLink = $zettleProductsLink;
        $this->settingsLink = $settingsLink;
    }
    public function renderHeader(): string
    {
        ob_start();
        ?>

        <h2>
            <?php 
        esc_html_e('WooCommerce is connected to PayPal Point of Sale', 'paypal-point-of-sale');
        ?>
        </h2>

        <?php 
        return (string) ob_get_clean();
    }
    public function renderContent(): string
    {
        ob_start();
        ?>

        <p>
            <?php 
        esc_html_e('In the future, when you make a sale or edit stock, we will update your stock in PayPal Point of Sale and WooCommerce.', 'paypal-point-of-sale');
        ?>
        </p>

        <p>
            <?php 
        esc_html_e('If you need to update your products, do this in WooCommerce and they\'ll sync to your PayPal Point of Sale app automatically.', 'paypal-point-of-sale');
        ?>
        </p>

        <p>
            <a class="link"
                rel="noopener noreferrer"
                target="_blank"
                href="<?php 
        echo esc_url_raw($this->zettleProductsLink['url']);
        ?>">
                <?php 
        echo esc_html($this->zettleProductsLink['title']);
        ?></a>

            <?php 
        if (!filter_input(\INPUT_GET, 'review', \FILTER_VALIDATE_BOOL)) {
            ?>
            <span class="separator">|</span>
                <a class="link"
                    href="<?php 
            echo esc_url_raw($this->settingsLink['url']);
            ?>">
                    <?php 
            echo esc_html($this->settingsLink['title']);
            ?></a>

            <?php 
        }
        ?>
        </p>

        <?php 
        return (string) ob_get_clean();
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
