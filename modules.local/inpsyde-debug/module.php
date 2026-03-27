<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use Inpsyde\Debug\InpsydeDebugModule;

return static function (): InpsydeDebugModule {
    return new InpsydeDebugModule();
};
