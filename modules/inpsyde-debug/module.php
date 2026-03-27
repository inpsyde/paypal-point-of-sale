<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Debug;

use Syde\Vendor\Zettle\Inpsyde\Debug\InpsydeDebugModule;
return static function (): InpsydeDebugModule {
    return new InpsydeDebugModule();
};
