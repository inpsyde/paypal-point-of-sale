<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\View;

use Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;

class SyncProgressView implements OnboardingView
{
    use ButtonRendererTrait;

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

    private string $pluginTitle;

    /**
     * SyncProgressView constructor.
     *
     * @param callable $supportedWcProductsCountQuery
     * @param callable $totalWcProductsCountQuery
     */
    public function __construct(
        callable $supportedWcProductsCountQuery,
        callable $totalWcProductsCountQuery,
        string $pluginTitle
    ) {

        $this->supportedWcProductsCountQuery = $supportedWcProductsCountQuery;
        $this->totalWcProductsCountQuery = $totalWcProductsCountQuery;
        $this->pluginTitle = $pluginTitle;
    }

    /**
     * @return string
     * phpcs:disable Syde.Functions.FunctionLength.TooLong
     */
    public function renderHeader(): string
    {
        ob_start(); ?>

        <h2>
            <?php esc_html_e('Initial sync in progress', 'paypal-point-of-sale'); ?>
        </h2>

        <?php return (string) ob_get_clean();
    }

    public function renderContent(): string
    {
        ob_start() ?>

        <p>
            <?php echo esc_html($this->stepDescriptionText()); ?>
        </p>
        <div class="sync-navigation-note">
            <strong>
                <?php
                esc_html_e(
                    'Please leave this window open until synchronization has finished.',
                    'paypal-point-of-sale'
                );
                echo wp_kses_post($this->navigationNotice());
                ?>
            </strong>
        </div>


        <div class="sync-progress" data-sync-progress="true">
            <div class="sync-progress-icon">
                <?php // phpcs:disable Syde.Files.LineLength.TooLong
                ?>
                <svg xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    x="0px"
                    y="0px"
                    viewBox="0 0 477.867 477.867"
                    xml:space="preserve">
                    <path d="M409.6,0c-9.426,0-17.067,7.641-17.067,17.067v62.344C304.667-5.656,164.478-3.386,79.411,84.479
                                c-40.09,41.409-62.455,96.818-62.344,154.454c0,9.426,7.641,17.067,17.067,17.067S51.2,248.359,51.2,238.933
                                c0.021-103.682,84.088-187.717,187.771-187.696c52.657,0.01,102.888,22.135,138.442,60.976l-75.605,25.207
                                c-8.954,2.979-13.799,12.652-10.82,21.606s12.652,13.799,21.606,10.82l102.4-34.133c6.99-2.328,11.697-8.88,11.674-16.247v-102.4
                                C426.667,7.641,419.026,0,409.6,0z" />
                    <path d="M443.733,221.867c-9.426,0-17.067,7.641-17.067,17.067c-0.021,103.682-84.088,187.717-187.771,187.696
                                c-52.657-0.01-102.888-22.135-138.442-60.976l75.605-25.207c8.954-2.979,13.799-12.652,10.82-21.606
                                c-2.979-8.954-12.652-13.799-21.606-10.82l-102.4,34.133c-6.99,2.328-11.697,8.88-11.674,16.247v102.4
                                c0,9.426,7.641,17.067,17.067,17.067s17.067-7.641,17.067-17.067v-62.345c87.866,85.067,228.056,82.798,313.122-5.068
                                c40.09-41.409,62.455-96.818,62.344-154.454C460.8,229.508,453.159,221.867,443.733,221.867z" />
                </svg>
                <?php // phpcs:enable Syde.Files.LineLength.TooLong
                ?>
            </div>

            <div class="sync-progress-text">
                <span class="sync-progress-message">
                    <?php esc_html_e('Preparing to sync products', 'paypal-point-of-sale') ?>
                </span>
                <span class="sync-progress-status">...</span>
            </div>

            <div class="sync-progress-action">
                <button
                    class="sync-progress-action-cancel btn"
                    type="submit"
                    name="cancel"
                    value="<?php esc_attr_e('Cancel export', 'paypal-point-of-sale'); ?>"
                >
                    <?php esc_html_e('Cancel export', 'paypal-point-of-sale'); ?>
                </button>
            </div>
        </div>

        <?php return (string) ob_get_clean();
    }

    /**
     * Format a tooltip with information about the
     * implications of navigating away from a running sync
     * @return string
     */
    public function navigationNotice(): string
    {
        return wc_help_tip(
            sprintf(
                /** translators: %1$s: Shortname of the Plugin */
                esc_html__(
                    'If you navigate away during sync, you can always come back and continue.
                        However, %1$s will only start working correctly once it is finished.',
                    'paypal-point-of-sale'
                ),
                $this->pluginTitle
            )
        );
    }

    public function renderProceedButton(): string
    {
        return $this->renderActionButton(
            ButtonAction::PROCEED,
            null,
            [
                'disabled' => true,
                'hidden' => true,
            ]
        );
    }

    public function renderBackButton(): string
    {
        return $this->renderActionButton(
            ButtonAction::BACK,
            null,
            [
                'disabled' => true,
            ]
        );
    }

    /**
     * Returns the text describing what happens here.
     * Displays WC products count instead of "All" when not all products are supported.
     *
     * @return string
     */
    private function stepDescriptionText(): string
    {
        if ($this->supportedWcProductsCount() !== $this->totalWcProductsCount()) {
            return sprintf(
            /* translators: %1$d: a number */
                __(
                    '%1$d WooCommerce products are now being synced to your PayPal Point of Sale library.',
                    'paypal-point-of-sale'
                ),
                $this->supportedWcProductsCount()
            );
        }

        return __(
            'All WooCommerce products are now being synced to your PayPal Point of Sale library.',
            'paypal-point-of-sale'
        );
    }

    private function totalWcProductsCount(): int
    {
        if ($this->totalWcProductsCount === null) {
            $this->totalWcProductsCount = (int) ($this->totalWcProductsCountQuery)();
        }

        return (int) $this->totalWcProductsCount;
    }

    private function supportedWcProductsCount(): int
    {
        if ($this->supportedWcProductsCount === null) {
            $this->supportedWcProductsCount = (int) ($this->supportedWcProductsCountQuery)();
        }

        return (int) $this->supportedWcProductsCount;
    }
}
