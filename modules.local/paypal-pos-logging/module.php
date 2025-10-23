<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Logging;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new ZettleLoggingModule();
};
