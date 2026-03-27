<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Logging;

return static function (): ZettleLoggingModule {
    return new ZettleLoggingModule();
};
