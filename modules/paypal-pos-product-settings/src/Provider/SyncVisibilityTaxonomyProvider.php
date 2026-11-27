<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Provider;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Taxonomy\ZettleSyncVisibilityTaxonomy;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
class SyncVisibilityTaxonomyProvider implements Provider
{
    private ZettleSyncVisibilityTaxonomy $syncVisibilityTaxonomy;
    public function __construct(ZettleSyncVisibilityTaxonomy $syncVisibilityTaxonomy)
    {
        $this->syncVisibilityTaxonomy = $syncVisibilityTaxonomy;
    }
    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        $this->syncVisibilityTaxonomy->create();
        return \true;
    }
}
