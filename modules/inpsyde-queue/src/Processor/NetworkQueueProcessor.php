<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Processor;

use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\QueueException;
use Syde\Vendor\Zettle\Inpsyde\Queue\Logger\LoggerProviderInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
/**
 * Class NetworkQueueProcessor
 *
 * Decorates another QueueProcessor and ensures
 * that the current blog is restored when all jobs are processed
 *
 * @package Inpsyde\Queue\Queue
 */
class NetworkQueueProcessor implements QueueProcessor, LoggerProviderInterface
{
    use DecoratingLoggingProviderTrait;
    private QueueProcessor $inner;
    /**
     * @var callable The function returning NetworkState object
     */
    private $networkStateFactory;
    /**
     * NetworkQueueProcessor constructor.
     *
     * @param QueueProcessor $inner
     * @param callable $networkStateFactory The function returning NetworkState object
     */
    public function __construct(QueueProcessor $inner, callable $networkStateFactory)
    {
        $this->inner = $inner;
        $this->networkStateFactory = $networkStateFactory;
    }
    /**
     * @inheritDoc
     */
    public function repository(): JobRepository
    {
        return $this->inner->repository();
    }
    /**
     * Lock the queue, walk over all available jobs, unlock again
     *
     * @return int
     * @throws QueueException
     */
    public function process(): int
    {
        $networkState = ($this->networkStateFactory)();
        $result = $this->inner->process();
        $networkState->restore();
        return $result;
    }
    protected function inner(): QueueProcessor
    {
        return $this->inner;
    }
}
