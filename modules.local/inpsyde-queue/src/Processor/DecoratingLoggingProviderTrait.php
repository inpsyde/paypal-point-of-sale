<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Processor;

use Inpsyde\Queue\Logger\LoggerProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait DecoratingLoggingProviderTrait
{
    abstract protected function inner(): QueueProcessor;

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $inner = $this->inner();
        if (!$inner instanceof LoggerAwareInterface) {
            return;
        }
        $inner->setLogger($logger);
    }

    /**
     * @inheritDoc
     */
    public function logger(): LoggerInterface
    {
        $inner = $this->inner();
        if (!$inner instanceof LoggerProviderInterface) {
            return new NullLogger();
        }

        return $inner->logger();
    }
}
