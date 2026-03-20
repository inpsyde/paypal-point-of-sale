<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Logging;

use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;

class ZettleLoggingModule implements ServiceModule
{
    use ModuleClassNameIdTrait;

    /**
     * @inheritDoc
     */
    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }
}
