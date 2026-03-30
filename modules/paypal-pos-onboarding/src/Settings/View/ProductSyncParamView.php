<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\View;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\SyncCollisionStrategy;
class ProductSyncParamView implements OnboardingView
{
    use ButtonRendererTrait;
    /**
     * @var callable
     */
    private $zettleProductsCountQuery;
    private ?int $zettleProductsCount = null;
    /**
     * @var callable
     */
    private $totalWcProductsCountQuery;
    private ?int $totalWcProductsCount = null;
    /**
     * @var callable
     */
    private $supportedWcProductsCountQuery;
    private ?int $supportedWcProductsCount = null;
    private array $documentationLink;
    /**
     * ProductSyncParamView constructor.
     *
     * @param callable $supportedWcProductsCountQuery
     * @param callable $totalWcProductsCountQuery
     * @param callable $zettleProductsCountQuery
     * @param array $documentationLink
     */
    public function __construct(callable $supportedWcProductsCountQuery, callable $totalWcProductsCountQuery, callable $zettleProductsCountQuery, array $documentationLink)
    {
        $this->supportedWcProductsCountQuery = $supportedWcProductsCountQuery;
        $this->totalWcProductsCountQuery = $totalWcProductsCountQuery;
        $this->zettleProductsCountQuery = $zettleProductsCountQuery;
        $this->documentationLink = $documentationLink;
    }
    public function renderHeader(): string
    {
        ob_start();
        ?>

        <h2>
            <?php 
        echo esc_html($this->productCountText());
        ?>
        </h2>
        <p>
            <?php 
        echo wp_kses_post($this->renderProductsCount());
        ?>

            <?php 
        if ($this->zettleProductsCount() > 0) {
            echo esc_html(__('How would you like to set up your PayPal Point of Sale library?', 'paypal-point-of-sale'));
        }
        ?>
        </p>

        <?php 
        return ob_get_clean();
    }
    /**
     * @inheritDoc
     */
    public function renderContent(): string
    {
        // we render but hide our choice-selector because otherwise no value will be sent
        // (the standard WC input is disabled on this step)
        // and the field will be reset
        // https://github.com/inpsyde/PayPal-Zettle-POS/pull/91#discussion_r447040871
        $noChoice = $this->zettleProductsCount() === 0;
        ob_start();
        ?>

        <div class="form-choice-selection" <?php 
        echo $noChoice ? 'style="display: none;"' : '';
        ?>>
            <div class="form-choice-selector">
                <div class="form-choice-selector-input">
                    <input type="radio" name="woocommerce_zettle_sync_collision_strategy" id="zettle-merge-products"
                            value="<?php 
        echo esc_attr(SyncCollisionStrategy::MERGE);
        ?>">
                </div>

                <div class="form-choice-selector-content">
                    <label for="zettle-merge-products">
                        <?php 
        esc_html_e('Add WooCommerce products', 'zettle-woocommerce');
        ?>
                    </label>

                    <p class="form-choice-selector-content-description">
                        <?php 
        esc_html_e('Synced WooCommerce products and stock quantities will be added to the existing PayPal Point of Sale library.', 'zettle-woocommerce');
        ?>
                    </p>
                </div>
            </div>

            <div class="form-choice-selector">
                <div class="form-choice-selector-input">
                    <input type="radio" name="woocommerce_zettle_sync_collision_strategy" id="zettle-wipe-products"
                            value="<?php 
        echo esc_attr(SyncCollisionStrategy::WIPE);
        ?>">
                </div>

                <div class="form-choice-selector-content">
                    <label for="zettle-wipe-products">
                        <?php 
        esc_html_e('Replace PayPal Point of Sale library', 'zettle-woocommerce');
        ?>
                    </label>

                    <p class="form-choice-selector-content-description">
                        <?php 
        esc_html_e('Replace your existing PayPal Point of Sale library with synced products and stock quantities from WooCommerce.', 'zettle-woocommerce');
        ?>
                    </p>
                </div>
            </div>
        </div>

        <?php 
        return ob_get_clean();
    }
    /**
     * @inheritDoc
     */
    public function renderProceedButton(): string
    {
        return $this->renderActionButton(ButtonAction::PROCEED);
    }
    /**
     * @inheritDoc
     */
    public function renderBackButton(): string
    {
        return $this->renderActionButton(ButtonAction::BACK);
    }
    /**
     * Render Products Count
     *
     * @return string
     */
    protected function renderProductsCount(): string
    {
        if ($this->supportedWcProductsCount() !== $this->totalWcProductsCount()) {
            return sprintf(
                /* translators: %3$s are the rendered link tag */
                __('%1$d WooCommerce products will be synced.
                                %2$d WooCommerce products will not be exported
                                because the product type is not supported by PayPal Point of Sale.
                                Read more about what product types are supported %3$s.', 'paypal-point-of-sale'),
                $this->supportedWcProductsCount(),
                $this->totalWcProductsCount() - $this->supportedWcProductsCount(),
                /** translators:
                 * %1$s title of the link
                 * %2$s are the link to the documentation page section
                 * %3$s action/click expression
                 */
                sprintf('<a class="link" rel="noopener noreferrer" target="_blank" href="%1$s" title="%2$s">%3$s</a>', esc_url_raw($this->documentationLink['url']), esc_html($this->documentationLink['title']), esc_html__('here', 'paypal-point-of-sale'))
            );
        }
        return esc_html(__('All WooCommerce products will be synced.', 'paypal-point-of-sale'));
    }
    /**
     * If there are no valid or unsupported products types,
     * we display the total woocommerce products amount,
     * to give the user feedback, which products valid/supported
     *
     * @return string
     */
    private function productCountText(): string
    {
        if ($this->supportedWcProductsCount() !== $this->totalWcProductsCount()) {
            return sprintf(
                /* translators:
                   %1$d: Number of WooCommerce valid Products,
                   %2$d: Number of total Products,
                   %3$d: Number of Zettle Products */
                __('%1$d out of %2$d WooCommerce + %3$d PayPal Point of Sale products found', 'paypal-point-of-sale'),
                $this->supportedWcProductsCount(),
                $this->totalWcProductsCount(),
                $this->zettleProductsCount()
            );
        }
        return sprintf(
            /* translators: Number of WooCommerce Products & Number of Zettle Products */
            __('%1$d WooCommerce + %2$d PayPal Point of Sale products found', 'paypal-point-of-sale'),
            $this->supportedWcProductsCount(),
            $this->zettleProductsCount()
        );
    }
    /**
     * @return int
     */
    private function zettleProductsCount(): int
    {
        if ($this->zettleProductsCount === null) {
            $this->zettleProductsCount = ($this->zettleProductsCountQuery)();
        }
        return $this->zettleProductsCount;
    }
    /**
     * @return int
     */
    private function totalWcProductsCount(): int
    {
        if ($this->totalWcProductsCount === null) {
            $this->totalWcProductsCount = ($this->totalWcProductsCountQuery)();
        }
        return $this->totalWcProductsCount;
    }
    /**
     * @return int
     */
    private function supportedWcProductsCount(): int
    {
        if ($this->supportedWcProductsCount === null) {
            $this->supportedWcProductsCount = ($this->supportedWcProductsCountQuery)();
        }
        return $this->supportedWcProductsCount;
    }
}
