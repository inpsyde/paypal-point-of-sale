<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth;

use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
return static function (): ModuleInterface {
    return new AuthModule();
};
