<?php

declare(strict_types=1);

namespace Inpsyde\Queue;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

trait ExceptionLoggingTrait
{

    /**
     * Produces an exception trace and passes that to the Logger.
     * if WP_DEBUG is active, the full trace is used, otherwise a shorter on will be produced
     *
     * @param Throwable $exception
     * @param LoggerInterface $logger
     * @param string $logLevel
     */
    protected function logException(
        Throwable $exception,
        LoggerInterface $logger,
        string $logLevel = LogLevel::WARNING
    ) {

        $isDebug = defined('WP_DEBUG') && WP_DEBUG;
        $logger->log(
            $logLevel,
            $isDebug
                ? $this->formatFullExceptionTrace($exception)
                : $this->formatShortExceptionTrace($exception)
        );
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
        $output = sprintf(
            '%1$s: %2$s in %3$s:%4$d',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        $innerException = $exception->getPrevious();
        if (!$innerException) {
            return $output;
        }

        return $this->formatShortExceptionTrace($innerException) . PHP_EOL . "Next $output";
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
        return (string) $exception;
    }
}
