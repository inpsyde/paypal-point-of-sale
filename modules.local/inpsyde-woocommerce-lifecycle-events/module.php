<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new WcEventsModule();
};
