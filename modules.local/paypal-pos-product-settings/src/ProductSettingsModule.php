<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Syde\PayPal\PointOfSale\BootableProviderAwareTrait;
use Syde\PayPal\PointOfSale\BootableProviderModuleInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\PayPal\PointOfSale\ProductSettings\Barcode\BarcodeInputField;
use Syde\PayPal\PointOfSale\ProductSettings\Barcode\VariantBarcodeSaveHandler;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as C;
use WP_Post;

class ProductSettingsModule implements ModuleInterface, BootableProviderModuleInterface
{
    use BootableProviderAwareTrait;

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(
            require __DIR__ . '/../services.php',
            require __DIR__ . '/../extensions.php'
        );
    }

    /**
     * @inheritDoc
     */
    public function run(C $container): void
    {
        // do this a bit later to make the filter adding more flexible
        // and avoid loading translations too early (WP 6.7)
        add_action('init', function () use ($container) {
            $this->bootProviders(
                $container,
                ...$container->get('paypal-pos.product-settings.provider')
            );

            if ($container->get('paypal-pos.product-settings.barcode.standard-ui-enabled')) {
                $this->addVariationBarcodeHandlers(
                    $container->get('paypal-pos.product-settings.barcode.input-field.variation'),
                    $container->get('paypal-pos.sdk.repository.woocommerce.product'),
                    $container->get('paypal-pos.product-settings.barcode.save-handler.variation')
                );
            }
        });
    }

    private function addVariationBarcodeHandlers(
        BarcodeInputField $barcodeField,
        ProductRepositoryInterface $wcProductRepository,
        VariantBarcodeSaveHandler $saveHandler
    ) {
        add_action('woocommerce_product_after_variable_attributes', static function (
            int $loop,
            array $variationData,
            WP_Post $variationPost
        ) use (
            $barcodeField,
            $wcProductRepository
        ) {
            $variation = $wcProductRepository->findById((int) $variationPost->ID);
            if (!$variation) {
                return;
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $barcodeField->render($variation, $loop);
        }, 10, 3);

        add_action('woocommerce_save_product_variation', [$saveHandler, 'save'], 10, 2);
    }
}
