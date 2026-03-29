<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Provider;

use Psr\Container\ContainerInterface as C;
use Syde\PayPal\PointOfSale\ProductSettings\Taxonomy\ZettleSyncVisibilityTaxonomy;
use Syde\PayPal\PointOfSale\Provider;

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

        return true;
    }
}
