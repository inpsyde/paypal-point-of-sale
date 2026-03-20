<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Queue;

return static function (): ZettleQueueModule {
    return new ZettleQueueModule();
};
