<?php

namespace Inpsyde\Debug;

use Throwable;

interface ExceptionFormatter
{

    public function format(Throwable $exception): string;
}
