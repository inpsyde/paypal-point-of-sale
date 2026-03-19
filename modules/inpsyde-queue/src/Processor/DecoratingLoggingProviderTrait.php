<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Processor;

use Syde\Vendor\Zettle\Psr\Log\LoggerAwareInterface;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Psr\Log\NullLogger;
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
