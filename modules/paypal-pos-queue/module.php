<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue;

return static function (): ZettleQueueModule {
    return new ZettleQueueModule();
};
