<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\OrderSync;

return static function (): OrderSyncModule {
    return new OrderSyncModule();
};
