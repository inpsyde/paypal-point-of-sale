<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface as C;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\PayPal\PointOfSale\ProductSettings\Barcode\BarcodeInputField;
use Syde\PayPal\PointOfSale\ProductSettings\Barcode\VariantBarcodeSaveHandler;
use WP_Post;

class ProductSettingsModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }

    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }

    public function run(C $container): bool
    {
        // do this a bit later to make the filter adding more flexible
        // and avoid loading translations too early (WP 6.7)
        add_action('init', function () use ($container) {
            foreach ($container->get('paypal-pos.product-settings.provider') as $provider) {
                $provider->boot($container);
            }

            if ($container->get('paypal-pos.product-settings.barcode.standard-ui-enabled')) {
                $this->addVariationBarcodeHandlers(
                    $container->get('paypal-pos.product-settings.barcode.input-field.variation'),
                    $container->get('paypal-pos.sdk.repository.woocommerce.product'),
                    $container->get('paypal-pos.product-settings.barcode.save-handler.variation')
                );
            }
        });

        return true;
    }

    private function addVariationBarcodeHandlers(
        BarcodeInputField $barcodeField,
        ProductRepositoryInterface $wcProductRepository,
        VariantBarcodeSaveHandler $saveHandler
    ): void {

        add_action('woocommerce_product_after_variable_attributes', static function (
            int $loop,
            array $variationData,
            WP_Post $variationPost
        ) use (
            $barcodeField,
            $wcProductRepository
        ): void {
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
