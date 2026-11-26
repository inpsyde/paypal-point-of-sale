<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync;

return static function (): OrderSyncModule {
    return new OrderSyncModule();
};
