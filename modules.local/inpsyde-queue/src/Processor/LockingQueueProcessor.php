<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Processor;

use Inpsyde\Queue\Exception\QueueLockedException;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Queue\Queue\Locker;

class LockingQueueProcessor implements QueueProcessor
{
    use DecoratingLoggingProviderTrait;

    /**
     * @var QueueProcessor
     */
    private $inner;

    /**
     * @var Locker
     */
    private $locker;

    public function __construct(QueueProcessor $inner, Locker $locker)
    {
        $this->inner = $inner;
        $this->locker = $locker;
    }

    public function repository(): JobRepository
    {
        return $this->inner()->repository();
    }

    public function process(): int
    {
        if ($this->locker->isLocked()) {
            throw new QueueLockedException(
                sprintf(
                    'The queue is currently locked by %s',
                    get_class($this->locker)
                )
            );
        }

        $this->locker->lock();

        /**
         * At the end of script execution, unlock the queue again in case some Job
         * caused an error that prevented this function from completing.
         *
         * We're using register_shutdown_function() instead of WordPress's 'shutdown'
         * hook since WP/WC will generously call that hook during regular error recovery
         */
        register_shutdown_function([$this->locker, 'unlock']);
        $result = $this->inner()->process();
        $this->locker->unlock();

        return $result;
    }

    protected function inner(): QueueProcessor
    {
        return $this->inner;
    }
}
