<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use Throwable;

interface ExceptionFormatter
{
    public function format(Throwable $exception): string;
}
