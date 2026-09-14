<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Provider;

use Psr\Container\ContainerInterface as C;
use Syde\PayPal\PointOfSale\ProductSettings\Components\ProductSettingsTab;
use Syde\PayPal\PointOfSale\Provider;

class CustomProductTabProvider implements Provider
{
    private ProductSettingsTab $settingsTab;

    /**
     * CustomProductTabProvider constructor.
     *
     * @param ProductSettingsTab $settingsTab
     */
    public function __construct(ProductSettingsTab $settingsTab)
    {
        $this->settingsTab = $settingsTab;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_filter(
            'woocommerce_product_data_tabs',
            function (mixed $tabs): mixed {
                if (!is_array($tabs)) {
                    return $tabs;
                }
                return $this->settingsTab->addTab($tabs);
            }
        );

        add_action(
            'admin_head',
            [$this->settingsTab, 'addCustomTabIcon']
        );

        add_action(
            'woocommerce_process_product_meta',
            [$this->settingsTab, 'saveFields']
        );

        $addBarcodeInput = $container->get('paypal-pos.product-settings.barcode.standard-ui-enabled');

        add_action(
            'woocommerce_product_data_panels',
            function () use ($addBarcodeInput) {
                $this->settingsTab->renderSettings($addBarcodeInput);
            }
        );

        return true;
    }
}
