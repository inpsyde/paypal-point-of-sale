<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Assets;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
class AssetsModule implements ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    /**
     * @inheritDoc
     */
    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }
    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }
}
