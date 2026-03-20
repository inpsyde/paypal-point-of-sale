<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Provider;

use Syde\PayPal\PointOfSale\ProductSettings\Taxonomy\ZettleSyncVisibilityTaxonomy;
use Syde\PayPal\PointOfSale\Provider;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;

class SyncVisibilityTaxonomyProvider implements Provider
{

    /**
     * @var ZettleSyncVisibilityTaxonomy
     */
    private $syncVisibilityTaxonomy;

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
