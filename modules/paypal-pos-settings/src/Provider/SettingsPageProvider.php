<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings\Provider;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
class SettingsPageProvider implements Provider
{
    /**
     * @var callable
     */
    private $pageFactory;
    /**
     * SettingsPageProvider constructor.
     *
     * @param callable $pageFactory
     */
    public function __construct(callable $pageFactory)
    {
        $this->pageFactory = $pageFactory;
    }
    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_filter('woocommerce_get_settings_pages', function ($settings): array {
            $settings[] = ($this->pageFactory)();
            return $settings;
        });
        return \true;
    }
}
