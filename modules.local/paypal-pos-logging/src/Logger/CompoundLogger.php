<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Logging\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\NullLogger;

class CompoundLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var LoggerInterface[]
     */
    private array $loggers;

    public function __construct(LoggerInterface ...$loggers)
    {
        $this->loggers = $loggers;
    }

    /**
     *  phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     *
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }

    public function addLogger(LoggerInterface $logger): self
    {
        if (!$logger instanceof NullLogger) {
            $this->loggers[] = $logger;
        }

        return $this;
    }
}
