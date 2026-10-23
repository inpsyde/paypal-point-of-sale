<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Debug;

use Throwable;
interface ExceptionHandler
{
    public function handle(Throwable $exception): void;
}
