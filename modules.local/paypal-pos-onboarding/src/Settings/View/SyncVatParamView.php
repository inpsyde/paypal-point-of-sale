<?php

declare(strict_types=1);

// phpcs:disable Syde.Functions.FunctionLength.TooLong

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\View;

use Syde\PayPal\PointOfSale\Onboarding\Comparison\StoreComparison;
use Syde\PayPal\PointOfSale\Onboarding\DataProvider\Store\StoreDataProvider;
use Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Tax\TaxRate;
use Syde\PayPal\PointOfSale\Sync\PriceSyncMode;

class SyncVatParamView implements OnboardingView
{
    use ButtonRendererTrait;

    private StoreComparison $storeComparison;

    private StoreDataProvider $remoteStoreData;

    private StoreDataProvider $localStoreData;

    /**
     * @var TaxRate[]|null
     */
    private ?array $defaultTaxRates = null;

    /**
     * @param TaxRate[]|null $defaultTaxRates
     */
    public function __construct(
        StoreComparison $storeComparison,
        StoreDataProvider $remoteStoreData,
        StoreDataProvider $localStoreData,
        ?array $defaultTaxRates
    ) {

        $this->storeComparison = $storeComparison;
        $this->remoteStoreData = $remoteStoreData;
        $this->localStoreData = $localStoreData;
        $this->defaultTaxRates = $defaultTaxRates;
    }

    /**
     * @inheritDoc
     *
     * phpcs:disable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
     */
    public function renderHeader(): string
    {
        ob_start(); ?>

        <h2>
            <?php esc_html_e('Product prices', 'paypal-point-of-sale'); ?>
        </h2>

        <?php if (!$this->storeComparison->priceSyncRequiresTaxSync()) : ?>
            <p>
                <strong>
                    <?php esc_html_e(
                        'Your taxes will not be imported, you may need to configure them manually in your PayPal Point of Sale account.',
                        'paypal-point-of-sale'
                    ); ?>
                </strong>
            </p>
        <?php endif; ?>

        <?php if ($this->defaultTaxRates !== null && empty($this->defaultTaxRates)) : ?>
            <div class="alert alert-warning">
                <?php esc_html_e(
                    'You do not have default tax rates in your PayPal Point of Sale account, you may want to add them before syncing products.',
                    'paypal-point-of-sale'
                ); ?>
            </div>
        <?php endif; ?>

        <?php if (!$this->storeComparison->canSyncPrices()) : ?>
            <p>
                <strong>
                    <?php esc_html_e(
                        'Your prices will not be imported because your settings in PayPal Point of Sale and WooCommerce do not match:',
                        'paypal-point-of-sale'
                    ); ?>
                </strong>
                <?php
                $messages = [];

                if (!$this->storeComparison->currency()) {
                    $messages[] = esc_html(
                        sprintf(
                            /* translators: %1$s, %2$s: Currency codes (EUR, GBP, ...) */
                            __(
                                'Currency: %1$s in PayPal Point of Sale, %2$s in WooCommerce.',
                                'paypal-point-of-sale'
                            ),
                            $this->remoteStoreData->currency(),
                            $this->localStoreData->currency()
                        )
                    );
                }

                if ($this->storeComparison->priceSyncRequiresTaxSync()) {
                    if (!$this->storeComparison->country()) {
                        $messages[] = esc_html(
                            sprintf(
                            /* translators: %1$s, %2$s: Country codes (UK, DE, ...) */
                                __(
                                    'Country: %1$s in PayPal Point of Sale, %2$s in WooCommerce.',
                                    'paypal-point-of-sale'
                                ),
                                $this->remoteStoreData->country(),
                                $this->localStoreData->country()
                            )
                        );
                    }

                    if (!$this->storeComparison->taxesEnabled()) {
                        $messages[] = esc_html(
                            sprintf(
                                __(
                                    'Taxes are disabled in WooCommerce.',
                                    'paypal-point-of-sale'
                                )
                            )
                        );
                    } elseif (!$this->storeComparison->taxRatesConfigured()) {
                        $messages[] = esc_html(
                            sprintf(
                                __(
                                    'Tax rates not added in WooCommerce.',
                                    'paypal-point-of-sale'
                                )
                            )
                        );
                    }
                }

                echo '<ul>' .
                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                    implode('', array_map(static function (string $msg): string {
                        return "<li><strong>$msg</strong></li>";
                    }, $messages)) .
                    '</ul>';
                ?>
            </p>
        <?php endif; ?>

        <?php if (!$this->storeComparison->includeTaxes() && $this->storeComparison->canSyncPrices()) : ?>
            <div class="alert alert-warning">
                <?php echo esc_html(
                    sprintf(
                        __(
                            'Remember that your tax inclusion settings in WooCommerce and PayPal Point of Sale do not match.
                            If you sync prices, the prices will be automatically adjusted to include/exclude taxes and your margins will change.',
                            'paypal-point-of-sale'
                        )
                    )
                ); ?>
            </div>
        <?php endif; ?>

        <?php return (string) ob_get_clean();
    }

    /**
     * @inheritDoc
     */
    public function renderContent(): string
    {
        ob_start();

        if ($this->storeComparison->canSyncPrices() && $this->storeComparison->priceSyncRequiresTaxSync()) { ?>
        <p>
            <?php echo esc_html(
                sprintf(
                    __(
                        'If you sync prices, your tax settings in WooCommerce and PayPal Point of Sale need to match.
                        If the sync is disabled, you can edit taxes in your PayPal Point of Sale library.',
                        'paypal-point-of-sale'
                    )
                )
            ); ?>
        </p>

        <p>
            <?php esc_html_e('Please choose an option below.', 'paypal-point-of-sale'); ?>
        </p>
            <?php
        }

        $this->renderFormChoiceSelection();

        return (string) ob_get_clean();
    }

    /**
     * @return string
     */
    public function renderFormChoiceSelection(): string
    {
        $disabled = !$this->storeComparison->canSyncPrices();
        $syncByDefault = !$disabled && $this->storeComparison->includeTaxes();

        ob_start(); ?>

        <div class="form-choice-selection">
            <div class="form-choice-selector<?= $syncByDefault ? ' active' : '' ?>
                <?php echo $disabled ? ' disabled' : ''; ?>">
                <div class="form-choice-selector-input">
                    <input id="zettle-include-tax-prices" type="radio" name="woocommerce_zettle_sync_price_strategy"
                            value="<?= esc_attr(PriceSyncMode::ENABLED) ?>"
                            <?= $disabled ? 'disabled' : ($syncByDefault ? 'checked' : '') ?>
                    >
                </div>

                <div class="form-choice-selector-content">
                    <label for="zettle-include-tax-prices">
                        <?php esc_html_e('Sync prices', 'paypal-point-of-sale'); ?>
                    </label>

                    <p class="form-choice-selector-content-description">
                        <?php esc_html_e(
                            'Edit prices at WooCommerce to keep them up-to-date in PayPal Point of Sale.',
                            'paypal-point-of-sale'
                        ); ?>
                    </p>
                </div>
            </div>

            <div class="form-choice-selector<?= !$syncByDefault ? ' active' : '' ?>">
                <div class="form-choice-selector-input">
                    <input id="zettle-zero-prices" type="radio" name="woocommerce_zettle_sync_price_strategy"
                            value="<?= esc_attr(PriceSyncMode::DISABLED) ?>"
                            <?= !$syncByDefault ? 'checked' : ''; ?>>
                </div>

                <div class="form-choice-selector-content">
                    <label for="zettle-zero-prices">
                        <?php esc_html_e("Don't sync prices", 'paypal-point-of-sale'); ?>
                    </label>

                    <p class="form-choice-selector-content-description">
                        <?php esc_html_e(
                            'Synced products will have the price set to 0. Update prices in your PayPal Point of Sale product library.',
                            'paypal-point-of-sale'
                        ); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php return (string) ob_get_contents();
    }

    /**
     * @inheritDoc
     */
    public function renderProceedButton(): string
    {
        return $this->renderActionButton(
            ButtonAction::PROCEED,
            __('Start sync', 'paypal-point-of-sale')
        );
    }

    /**
     * @inheritDoc
     */
    public function renderBackButton(): string
    {
        return $this->renderActionButton(ButtonAction::BACK);
    }
}
