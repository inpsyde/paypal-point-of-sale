<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new WcStatusReportModule();
};
