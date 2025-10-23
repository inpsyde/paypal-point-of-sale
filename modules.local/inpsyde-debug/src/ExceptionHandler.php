<?php

namespace Inpsyde\Debug;

use Throwable;

interface ExceptionHandler
{

    public function handle(Throwable $exception): void;
}
