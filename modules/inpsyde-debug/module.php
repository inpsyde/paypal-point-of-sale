<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Debug;

use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Zettle\Inpsyde\Debug\InpsydeDebugModule;
return static function (): ModuleInterface {
    return new InpsydeDebugModule();
};
