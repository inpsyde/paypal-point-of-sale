<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Metabox;

use Syde\Vendor\Zettle\MetaboxOrchestra\AdminNotices;
use Syde\Vendor\Zettle\MetaboxOrchestra\BoxAction;
class ReadonlyMetaboxAction implements BoxAction
{
    /**
     * @inheritDoc
     */
    public function save(AdminNotices $notices): bool
    {
        return \false;
    }
}
