<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Processor;

use Syde\Vendor\Zettle\Inpsyde\Queue\Logger\LoggerProviderInterface;
use Syde\Vendor\Zettle\Psr\Log\LoggerAwareInterface;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Psr\Log\NullLogger;
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
