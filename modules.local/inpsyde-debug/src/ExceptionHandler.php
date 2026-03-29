<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use Throwable;

interface ExceptionHandler
{
    public function handle(Throwable $exception): void;
}
