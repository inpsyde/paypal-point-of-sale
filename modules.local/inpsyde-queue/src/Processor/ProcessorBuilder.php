<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Processor;

use Inpsyde\Queue\NetworkState;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\JobIterator;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Queue\Queue\Locker;
use Inpsyde\Queue\Queue\QueueWalker;
use Inpsyde\Queue\Queue\StoppableQueueWalker;
use Inpsyde\Queue\Queue\Stopper;
use Inpsyde\Queue\Queue\UnstoppableQueueWalker;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Streamlines the creation of QueueProcessor instances with a fluent API
 */
class ProcessorBuilder
{

    /**
     * @var EphemeralJobRepository
     */
    private $repository;

    /**
     * @var Stopper|null
     */
    private $stopper;

    /**
     * @var array
     */
    private $types = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobRecordFactoryInterface
     */
    private $jobRecordFactory;

    /**
     * @var Locker|null
     */
    private $locker;

    /**
     * @var bool
     */
    private $isMultisite = false;

    /**
     * @var callable
     */
    private $exceptionHandler;

    /**
     * @var int
     */
    private $maxRetriesCount = 0;

    public function __construct(
        JobRecordFactoryInterface $jobRecordFactory
    ) {

        $this->repository = new EphemeralJobRepository();
        $this->jobRecordFactory = $jobRecordFactory;
    }

    public function withStopper(Stopper $stopper): self
    {
        $this->stopper = $stopper;

        return $this;
    }

    /**
     * Adds a Locker to the Builder
     * This will cause usage of the LockingQueueProcessor decorator during build()
     * @param Locker $locker
     *
     * @return $this
     */
    public function withLocker(Locker $locker): self
    {
        $this->locker = $locker;

        return $this;
    }

    /**
     * Uses a specific JobRepository. By default, EphemeralJobRepository is used
     * @param JobRepository $repository
     *
     * @return $this
     */
    public function withRepository(JobRepository $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Makes the resulting processor work only on the specified types of jobs
     * @param array $types
     *
     * @return $this
     */
    public function withJobTypes(array $types): self
    {
        $this->types = $types;

        return $this;
    }

    /**
     * If this is configured, the Processor will be built wrapped in a NetworkQueueProcessor
     * @param bool $state
     *
     * @return $this
     */
    public function withNetworkSupport(bool $state = true): self
    {
        $this->isMultisite = $state;

        return $this;
    }

    public function withExceptionHandler(callable $handler): self
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    public function withMaxRetriesCount(int $maxRetriesCount): self
    {
        $this->maxRetriesCount = $maxRetriesCount;

        return $this;
    }

    public function build(): QueueProcessor
    {
        $jobIterator = new JobIterator($this->repository, $this->types);
        $queueWalker = $this->createWalker($jobIterator);

        $queueProcessor = new BasicQueueProcessor(
            $this->repository,
            $this->jobRecordFactory,
            $queueWalker,
            $this->logger ?? new NullLogger(),
            $this->maxRetriesCount,
            $this->exceptionHandler ?? static function () {
            }
        );

        if ($this->locker) {
            $queueProcessor = new LockingQueueProcessor($queueProcessor, $this->locker);
        }

        if ($this->isMultisite) {
            $queueProcessor = new NetworkQueueProcessor(
                $queueProcessor,
                static function (): NetworkState {
                    return NetworkState::create();
                }
            );
        }

        return $queueProcessor;
    }

    private function createWalker(JobIterator $jobIterator): QueueWalker
    {
        if ($this->stopper) {
            return new StoppableQueueWalker(
                $jobIterator,
                $this->stopper
            );
        }

        return new UnstoppableQueueWalker($jobIterator);
    }
}
