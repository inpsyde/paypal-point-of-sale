<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use Dhii\Modular\Module\ModuleInterface;
use Inpsyde\Debug\InpsydeDebugModule;

return static function (): ModuleInterface {
    return new InpsydeDebugModule();
};
