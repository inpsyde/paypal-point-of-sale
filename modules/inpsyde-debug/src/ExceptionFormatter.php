<?php

namespace Syde\Vendor\Zettle\Inpsyde\Debug;

use Throwable;
interface ExceptionFormatter
{
    public function format(Throwable $exception): string;
}
