<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Components;

use Exception;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\BarcodeInputField;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository\BarcodeSaverInterface;
use WC_Product;
class ProductSettingsTab
{
    public const SECTION_KEY = 'zettle-integration';
    public const SYNC_EXCLUSION_ID = '_zettle_exclude_from_sync';
    public const NONCE_KEY = 'zettle_integration_settings_nonce';
    public const NONCE_FIELD = 'zettle_integration_settings';
    private LoggerInterface $logger;
    private TermManager $termManager;
    private ProductRepositoryInterface $wcProductRepository;
    private BarcodeInputField $barcodeField;
    private BarcodeSaverInterface $barcodeSaver;
    public function __construct(LoggerInterface $logger, TermManager $termManager, ProductRepositoryInterface $wcProductRepository, BarcodeInputField $barcodeField, BarcodeSaverInterface $barcodeSaver)
    {
        $this->logger = $logger;
        $this->termManager = $termManager;
        $this->wcProductRepository = $wcProductRepository;
        $this->barcodeField = $barcodeField;
        $this->barcodeSaver = $barcodeSaver;
    }
    /**
     * @return string
     */
    public function sectionKey(): string
    {
        return self::SECTION_KEY;
    }
    /**
     * @return string
     */
    public function syncExclusionId(): string
    {
        return self::SYNC_EXCLUSION_ID;
    }
    public function barcodeId(): string
    {
        return $this->barcodeField->name();
    }
    /**
     * @return string
     */
    public function nonceKey(): string
    {
        return self::NONCE_KEY;
    }
    /**
     * @return string
     */
    public function nonceField(): string
    {
        return self::NONCE_FIELD;
    }
    public function addTab(array $tabs): array
    {
        if (!isset($tabs[$this->sectionKey()])) {
            $tabs[$this->sectionKey()] = ['label' => __('PayPal Point of Sale', 'paypal-point-of-sale'), 'target' => 'zettle_integration_panel', 'priority' => 80];
        }
        return $tabs;
    }
    /**
     * Add Custom Icon instead of the Default one at the Product Settings Tab
     *
     * @return void
     */
    public function addCustomTabIcon(): void
    {
        ?>

        <style>
            #woocommerce-product-data ul.wc-tabs li.zettle-integration_tab a:before {
                font-family: WooCommerce;
                content: '\e900';
            }
        </style>

        <?php 
    }
    /**
     * Add Settings into the Settings Panel
     *
     * @return void
     *
     * phpcs:disable Syde.Functions.FunctionLength.TooLong
     */
    public function renderSettings(bool $addBarcodeInput): void
    {
        global $post;
        $product = $this->wcProductRepository->findById((int) $post->ID);
        if (!$product instanceof WC_Product) {
            return;
        }
        ?>
        <div id="zettle_integration_panel" class="panel woocommerce_options_panel">
            <h2>
                <?php 
        esc_html_e('Product Library Sync', 'paypal-point-of-sale');
        ?>
            </h2>

            <div class="options_group">
                <?php 
        wp_nonce_field($this->nonceField(), $this->nonceKey());
        woocommerce_wp_checkbox(['id' => $this->syncExclusionId(), 'label' => esc_html__('Exclude from Sync?', 'paypal-point-of-sale'), 'value' => $this->getValue($this->termManager->hasTerm((int) $post->ID)), 'wrapper_class' => 'form-field-checkbox', 'desc_tip' => \true, 'description' => __(
            /** @lang text */
            'Select this option to exclude this Product from the PayPal Point of Sale Product Sync.',
            'paypal-point-of-sale'
        )]);
        ?>
            </div>

            <?php 
        if ($addBarcodeInput) {
            ?>
                <div class="show_if_simple">
                    <h2>
                        <?php 
            esc_html_e('Barcode', 'paypal-point-of-sale');
            ?>
                    </h2>

                    <div class="options_group">
                        <?php 
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->barcodeField->render($product);
            ?>
                    </div>
                </div>
                <?php 
        }
        ?>
        </div>
        <?php 
    }
    public function saveFields(mixed $postId): void
    {
        $data = $this->parseRequest();
        try {
            $this->validateRequest($data);
        } catch (\Throwable $exception) {
            $this->logger->warning(sprintf('Product settings validation failed: %1$s', $exception->getMessage()));
            return;
        }
        $productId = (int) $postId;
        $product = $this->wcProductRepository->findById($productId);
        if (!$product) {
            $this->logger->warning(sprintf('Product %1$d not found during product settings saving.', $productId));
            return;
        }
        $barcode = $data[$this->barcodeId()];
        if ($barcode !== null) {
            $this->barcodeSaver->save($product, $barcode);
        }
        $syncExclusion = $data[$this->syncExclusionId()];
        $hasTerm = $this->termManager->hasTerm($productId);
        if ($syncExclusion && !$hasTerm) {
            $this->termManager->setTerm($productId);
            return;
        }
        if (!$syncExclusion && $hasTerm) {
            $this->termManager->removeTerm($productId);
            return;
        }
    }
    /**
     * @param bool $state
     *
     * @return string
     */
    private function getValue(bool $state): string
    {
        return $state ? 'yes' : 'no';
    }
    /**
     * phpcs:disable WordPressVIPMinimum.Security.PHPFilterFunctions.MissingThirdParameter
     */
    private function parseRequest(): array
    {
        $barcode = filter_input(\INPUT_POST, $this->barcodeId());
        $syncExclusion = filter_input(\INPUT_POST, $this->syncExclusionId());
        return [$this->nonceKey() => $this->sanitizeText((string) filter_input(\INPUT_POST, $this->nonceKey())), $this->syncExclusionId() => $syncExclusion === null ? null : $this->sanitizeText((string) $syncExclusion), $this->barcodeId() => $barcode === null ? null : $this->sanitizeText((string) $barcode)];
    }
    private function sanitizeText(string $text): string
    {
        return sanitize_text_field(stripslashes($text));
    }
    private function validateRequest(array $data): void
    {
        $nonce = $data[$this->nonceKey()];
        if (!$nonce) {
            throw new Exception('Nonce not found.');
        }
        if (!wp_verify_nonce($nonce, $this->nonceField())) {
            throw new Exception('Nonce validation failed.');
        }
    }
}
