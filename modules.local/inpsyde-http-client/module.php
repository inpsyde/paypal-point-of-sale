<?php

declare(strict_types=1);

namespace Inpsyde\Http;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new HttpClientModule();
};
