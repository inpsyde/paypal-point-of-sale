<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
trait BootableProviderAwareTrait
{
    /**
     * @param C $container
     * @param Provider ...$providers
     */
    final public function bootProviders(C $container, Provider ...$providers): void
    {
        foreach ($providers as $provider) {
            $provider->boot($container);
        }
    }
}
