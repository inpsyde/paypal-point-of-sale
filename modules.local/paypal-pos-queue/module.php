<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Queue;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new ZettleQueueModule();
};
