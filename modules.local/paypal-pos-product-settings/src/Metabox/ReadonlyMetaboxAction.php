<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Metabox;

use MetaboxOrchestra\AdminNotices;
use MetaboxOrchestra\BoxAction;

class ReadonlyMetaboxAction implements BoxAction
{

    /**
     * @inheritDoc
     */
    public function save(AdminNotices $notices): bool
    {
        return false;
    }
}
