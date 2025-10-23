<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * Class ArrayLogger
 *
 * This is a special logger implementation that can return everything it logged.
 * Its primary use-case is to enable putting queue logs into our REST response.
 *
 * It optionally decorates a child LoggerInterface so that it can be used
 * on top of an existing logging chain
 *
 * @package Inpsyde\Queue\Log
 */
class ArrayLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var array
     */
    private $storage = [];

    /**
     * @var LoggerInterface|null
     */
    private $childLogger;

    public function __construct(LoggerInterface $childLogger = null)
    {
        $this->childLogger = $childLogger;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        $this->storage[$level][] = [
            'message' => $message,
            'context' => $context,
        ];
        if ($this->childLogger) {
            $this->childLogger->log($level, $message, $context);
        }
    }

    /**
     * Return the logs of a specific LogLevel, or all logs if no parameter is given
     *
     * @param string|null $logLevel
     *
     * @return array
     */
    public function logs(string $logLevel = null): array
    {
        if (!$logLevel) {
            return $this->storage;
        }
        if (!isset($this->storage[$logLevel])) {
            return [];
        }

        return $this->storage[$logLevel];
    }
}
