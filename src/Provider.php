<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
/**
 * Interface for bootable Provider
 */
interface Provider
{
    /**
     * @param C $container
     *
     * @return bool
     */
    public function boot(C $container): bool;
}
