<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

class LogExceptionHandler implements ExceptionHandler
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ExceptionFormatter
     */
    private $formatter;

    /**
     * @var string[]
     */
    private $levels;

    public function __construct(
        LoggerInterface $logger,
        ExceptionFormatter $formatter,
        array $levels
    ) {

        $this->logger = $logger;
        $this->formatter = $formatter;
        $this->levels = $levels;
    }

    public function handle(Throwable $exception): void
    {
        $this->logger->log($this->determineLogLevel($exception), $this->formatter->format($exception));
    }

    private function determineLogLevel(Throwable $exception): string
    {
        return $this->pluckLevel(class_parents($exception), $this->levels)
            ?? $this->pluckLevel(class_implements($exception), $this->levels)
            ?? LogLevel::INFO;
    }

    private function pluckLevel(array $keys, array $levels): ?string
    {
        foreach ($keys as $key) {
            if (isset($levels[$key])) {
                return $levels[$key];
            }
        }

        return null;
    }
}
