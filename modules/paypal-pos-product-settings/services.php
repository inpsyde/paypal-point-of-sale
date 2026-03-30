<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings;

use Syde\Vendor\Zettle\MetaboxOrchestra\BoxAction;
use Syde\Vendor\Zettle\MetaboxOrchestra\BoxView;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\BarcodeInputField;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository\BarcodeRepository;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\VariantBarcodeSaveHandler;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Components\ProductSettingsTab;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Components\TermManager;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Handler\ProductExcludeHandler;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Metabox\ReadonlyMetaboxAction;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Metabox\ZettleProductLibraryLink;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Metabox\ZettleProductLibraryLinkView;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Provider\CustomProductTabProvider;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Provider\ProductExcludeProvider;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Provider\SyncVisibilityTaxonomyProvider;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Taxonomy\ZettleSyncVisibilityTaxonomy;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
return ['paypal-pos.product-settings.taxonomy.sync-visibility.key' => static function (C $container): string {
    return 'zettle_sync_visibility';
}, 'paypal-pos.product-settings.taxonomy.sync-visibility.post-type' => static function (C $container): string {
    return 'product';
}, 'paypal-pos.product-settings.taxonomy.sync-visibility' => static function (C $container): ZettleSyncVisibilityTaxonomy {
    return new ZettleSyncVisibilityTaxonomy($container->get('paypal-pos.product-settings.taxonomy.sync-visibility.key'), $container->get('paypal-pos.product-settings.taxonomy.sync-visibility.post-type'));
}, 'paypal-pos.product-settings.term.excluded.name' => static function (): string {
    return __('PayPal Point of Sale Excluded', 'paypal-point-of-sale');
}, 'paypal-pos.product-settings.term.excluded.slug' => static function (): string {
    return __('zettle-excluded', 'paypal-point-of-sale');
}, 'paypal-pos.product-settings.term.excluded' => static function (C $container): TermManager {
    $syncVisibilityTaxonomy = $container->get('paypal-pos.product-settings.taxonomy.sync-visibility');
    assert($syncVisibilityTaxonomy instanceof ZettleSyncVisibilityTaxonomy);
    return new TermManager($container->get('paypal-pos.product-settings.term.excluded.name'), $container->get('paypal-pos.product-settings.term.excluded.slug'), $syncVisibilityTaxonomy->key());
}, 'paypal-pos.product-settings.product-settings-tab' => static function (C $container): ProductSettingsTab {
    return new ProductSettingsTab($container->get('paypal-pos.logger'), $container->get('paypal-pos.product-settings.term.excluded'), $container->get('paypal-pos.sdk.repository.woocommerce.product'), $container->get('paypal-pos.product-settings.barcode.input-field.simple'), $container->get('paypal-pos.product-settings.barcode.repository'));
}, 'paypal-pos.product-settings.handler.exclude' => static function (C $container): ProductExcludeHandler {
    return new ProductExcludeHandler($container->get('paypal-pos.sdk.repository.woocommerce.product'), $container->get('paypal-pos.product-settings.term.excluded'), $container->get('inpsyde.queue.repository'), $container->get('inpsyde.queue.create-job-record'));
}, 'paypal-pos.product-settings.zettle.product.base-link' => static function (C $container): string {
    return esc_url_raw('https://my.zettle.com/products');
}, 'paypal-pos.product-settings.zettle.product.title' => static function (C $container): string {
    return esc_html__('PayPal Point of Sale Product Library Link', 'paypal-point-of-sale');
}, 'paypal-pos.product-settings.metabox.product.library.link.view' => static function (C $container): BoxView {
    return new ZettleProductLibraryLinkView($container->get('paypal-pos.product-settings.zettle.product.base-link'));
}, 'paypal-pos.product-settings.metabox.product.library.link.action' => static function (): BoxAction {
    return new ReadonlyMetaboxAction();
}, 'paypal-pos.product-settings.metabox.product.library.link' => static function (C $container): ZettleProductLibraryLink {
    return new ZettleProductLibraryLink($container->get('paypal-pos.sdk.repository.zettle.product'), $container->get('paypal-pos.product-settings.metabox.product.library.link.view'), $container->get('paypal-pos.product-settings.metabox.product.library.link.action'), $container->get('paypal-pos.product-settings.zettle.product.title'));
}, 'paypal-pos.product-settings.provider.sync-visibility' => static function (C $container): Provider {
    return new SyncVisibilityTaxonomyProvider($container->get('paypal-pos.product-settings.taxonomy.sync-visibility'));
}, 'paypal-pos.product-settings.provider.product-settings-tab' => static function (C $container): Provider {
    return new CustomProductTabProvider($container->get('paypal-pos.product-settings.product-settings-tab'));
}, 'paypal-pos.product-settings.provider.product-exclude-handler' => static function (C $container): Provider {
    return new ProductExcludeProvider($container->get('paypal-pos.product-settings.handler.exclude'));
}, 'paypal-pos.product-settings.provider' => static function (C $container): array {
    return [$container->get('paypal-pos.product-settings.provider.sync-visibility'), $container->get('paypal-pos.product-settings.provider.product-settings-tab'), $container->get('paypal-pos.product-settings.provider.product-exclude-handler')];
}, 'paypal-pos.product-settings.is-product-editor' => static function (C $container): callable {
    return static function () use ($container): bool {
        if (!isset($_SERVER['SCRIPT_FILENAME'])) {
            return \false;
        }
        // using only to compare value, not storing anywhere.
        // phpcs:ignore WordPress.Security, using only to compare value, not storing anywhere.
        $currentView = $_SERVER['SCRIPT_FILENAME'] ?? '';
        if (!$currentView) {
            return \false;
        }
        $currentView = basename($currentView, '.php');
        if ($currentView === 'post-new') {
            // see https://bugs.php.net/bug.php?id=49184
            // phpcs:ignore WordPress.Security, using only to compare value, not storing anywhere.
            $type = $_GET['post_type'] ?? '';
            return $type === 'product';
        } elseif ($currentView === 'post') {
            // phpcs:ignore WordPress.Security, using only to compare value, not storing anywhere.
            $action = wp_unslash($_GET['action'] ?? '');
            if ($action !== 'edit') {
                return \false;
            }
            return $container->get('paypal-pos.product-settings.product.is-product')($container->get('paypal-pos.product-settings.product-editor.product-from-url')());
        }
        return \false;
    };
}, 'paypal-pos.product-settings.product-editor.product-from-url' => static function (C $container): callable {
    return static function (int $method = \INPUT_GET): int {
        /** @psalm-suppress ArgumentTypeCoercion */
        return (int) filter_input($method, 'post', \FILTER_VALIDATE_INT);
    };
}, 'paypal-pos.product-settings.product.is-product' => static function (C $container): callable {
    return static function (int $productId) use ($container): bool {
        $repository = $container->get('paypal-pos.sdk.repository.woocommerce.product');
        assert($repository instanceof ProductRepositoryInterface);
        $product = $repository->findById($productId);
        if ($product === null) {
            return \false;
        }
        return \true;
    };
}, 'paypal-pos.product-settings.barcode.input-field.name' => static function (): string {
    return '_zettle_barcode';
}, 'paypal-pos.product-settings.barcode.meta-key' => static function (): string {
    return '_zettle_barcode';
}, 'paypal-pos.product-settings.barcode.input-field.simple' => static function (C $container): BarcodeInputField {
    return new BarcodeInputField($container->get('paypal-pos.product-settings.barcode.input-field.name'), $container->get('paypal-pos.product-settings.barcode.repository'), __('Product barcode', 'paypal-point-of-sale'), 'zettle-simple-product-barcode');
}, 'paypal-pos.product-settings.barcode.input-field.variation' => static function (C $container): BarcodeInputField {
    return new BarcodeInputField($container->get('paypal-pos.product-settings.barcode.input-field.name'), $container->get('paypal-pos.product-settings.barcode.repository'), __('Barcode', 'paypal-point-of-sale'), 'zettle-variation-product-barcode form-field form-row');
}, 'paypal-pos.product-settings.barcode.repository' => static function (C $container): BarcodeRepository {
    return new BarcodeRepository($container->get('paypal-pos.product-settings.barcode.meta-key'), 'paypal-point-of-sale.barcode.value');
}, 'paypal-pos.product-settings.barcode.save-handler.variation' => static function (C $container): VariantBarcodeSaveHandler {
    return new VariantBarcodeSaveHandler($container->get('paypal-pos.product-settings.barcode.repository'), $container->get('paypal-pos.product-settings.barcode.input-field.variation'), $container->get('paypal-pos.sdk.repository.woocommerce.product'), $container->get('paypal-pos.logger'));
}, 'paypal-pos.product-settings.barcode.standard-ui-enabled' => static function (): bool {
    return apply_filters('paypal-point-of-sale.barcode.standard-input-ui-enabled', \true);
}];
