<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
interface BootableProviderModuleInterface
{
    /**
     * @param C $container
     * @param Provider ...$providers
     */
    public function bootProviders(C $container, Provider ...$providers): void;
}
