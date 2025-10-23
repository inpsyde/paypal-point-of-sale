<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait DecoratingLoggingProviderTrait
{

    abstract protected function inner(): QueueProcessor;

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoSetter
     */
    public function setLogger(LoggerInterface $logger)
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
        if (!$inner instanceof LoggerAwareInterface) {
            return new NullLogger();
        }

        return $inner->logger();
    }
}
