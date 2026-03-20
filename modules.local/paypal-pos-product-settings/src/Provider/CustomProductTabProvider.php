<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Provider;

use Syde\PayPal\PointOfSale\ProductSettings\Components\ProductSettingsTab;
use Syde\PayPal\PointOfSale\Provider;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;

class CustomProductTabProvider implements Provider
{

    /**
     * @var ProductSettingsTab
     */
    private $settingsTab;

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
            [$this->settingsTab, 'addTab']
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
