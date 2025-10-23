<?php

declare(strict_types=1);

namespace Inpsyde\Queue;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new QueueModule();
};
