<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use Throwable;

class FallbackFormatter implements ExceptionFormatter
{
    /**
     * @var bool
     */
    private $isDebug;

    public function __construct(bool $isDebug)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * Produces a recursive exception trace.
     * If WP_DEBUG is active, the full trace is used, otherwise a shorter on will be produced
     *
     * @param Throwable $exception
     *
     * @return string
     */
    public function format(Throwable $exception): string
    {
        return $this->isDebug
            ? $this->formatFullExceptionTrace($exception)
            : $this->formatShortExceptionTrace($exception);
    }

    /**
     * Recursively formats the exception and all its ascendant into a short error message
     *
     * @param Throwable $exception
     *
     * @return string
     */
    private function formatShortExceptionTrace(Throwable $exception): string
    {
        return sprintf(
            '%1$s: %2$s in %3$s:%4$d',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
    }

    /**
     * Returns the full exception trace by string casting
     *
     * @param Throwable $exception
     *
     * @return string
     */
    private function formatFullExceptionTrace(Throwable $exception): string
    {
        return sprintf(
            '%1$s%2$s%3$s',
            $this->formatShortExceptionTrace($exception),
            PHP_EOL,
            $exception->getTraceAsString()
        );
    }
}
