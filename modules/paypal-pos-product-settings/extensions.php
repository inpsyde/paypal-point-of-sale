<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings;

use Inpsyde\Assets\BaseAsset;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Components\TermManager;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
return ['paypal-pos.sync.product.sync-active-for-id' => static function (callable $previous, C $container): callable {
    return static function (int $productId) use ($container, $previous): bool {
        if (!$previous($productId)) {
            return \false;
        }
        $repository = $container->get('paypal-pos.sdk.repository.woocommerce.product');
        assert($repository instanceof ProductRepositoryInterface);
        $product = $repository->findByIdOrVariationId($productId);
        if (!$product) {
            return \false;
        }
        $excludedFromSync = $container->get('paypal-pos.product-settings.term.excluded');
        assert($excludedFromSync instanceof TermManager);
        return !$excludedFromSync->hasTerm((int) $product->get_id());
    };
}, 'inpsyde.assets.registry' => static function (array $previous, C $container): array {
    $assetUri = rtrim(plugins_url('/assets/', __DIR__ . '/paypal-point-of-sale.php'), '/\\');
    $isProductsEditor = $container->get('paypal-pos.product-settings.is-product-editor');
    // Products Editor Style
    $productEditorStyle = (new Style('zettle-product-editor-style', "{$assetUri}/products-style.css", BaseAsset::BACKEND))->canEnqueue($isProductsEditor());
    // Products Editor Script
    $productEditorScript = (new Script('zettle-products-script', "{$assetUri}/products-editor.js", BaseAsset::BACKEND))->canEnqueue($isProductsEditor())->withLocalize('zettleBarcodeScanning', ['initErrorMessage' => __('Failed to start scanning. Please check your camera and try again.', 'paypal-point-of-sale')]);
    return array_merge([$productEditorStyle, $productEditorScript], $previous);
}, 'inpsyde.metabox.registry' => static function (array $previous, C $container): array {
    $previous[] = $container->get('paypal-pos.product-settings.metabox.product.library.link');
    return $previous;
}];
