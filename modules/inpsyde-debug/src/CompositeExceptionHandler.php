<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Debug;

use Throwable;
class CompositeExceptionHandler implements ExceptionHandler
{
    /**
     * @var ExceptionHandler[]
     */
    private $exceptionHandlers;
    public function __construct(ExceptionHandler ...$exceptionHandlers)
    {
        $this->exceptionHandlers = $exceptionHandlers;
    }
    public function handle(Throwable $exception): void
    {
        foreach ($this->exceptionHandlers as $exceptionHandler) {
            $exceptionHandler->handle($exception);
        }
    }
}
