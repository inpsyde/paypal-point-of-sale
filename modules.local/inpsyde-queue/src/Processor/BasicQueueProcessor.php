<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Processor;

use Inpsyde\Queue\Exception\InvalidJobException;
use Inpsyde\Queue\Exception\QueueLockedException;
use Inpsyde\Queue\Logger\LoggerProviderInterface;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRecord;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Queue\Exception\QueueRuntimeException;
use Inpsyde\Queue\ExceptionLoggingTrait;
use Inpsyde\Queue\Queue\Locker;
use Inpsyde\Queue\Queue\QueueWalker;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

/**
 * Class NetworkQueueProcessor
 *
 * Basically performs the current queue tasks in the process() method. Each site of the
 * network will execute this process in a cron job, bound to the action NetworkQueueProcessor::HOOK.
 *
 * @package Inpsyde\Queue\Queue
 */
class BasicQueueProcessor implements QueueProcessor, LoggerProviderInterface
{
    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var QueueWalker
     */
    private $walker;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobRecordFactoryInterface
     */
    private $recordFactory;

    /**
     * @var callable
     */
    private $exceptionHandler;

    /**
     * @var int
     */
    private $maxRetriesCount;

    public function __construct(
        JobRepository $jobRepository,
        JobRecordFactoryInterface $recordFactory,
        QueueWalker $walker,
        LoggerInterface $logger,
        int $maxRetriesCount,
        ?callable $exceptionHandler = null
    ) {

        $this->jobRepository = $jobRepository;
        $this->recordFactory = $recordFactory;
        $this->walker = $walker;
        $this->logger = $logger;
        $this->maxRetriesCount = $maxRetriesCount;
        $this->exceptionHandler = $exceptionHandler ?? static function () {
        };
    }

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoSetter
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function repository(): JobRepository
    {
        return $this->jobRepository;
    }

    /**
     * Lock the queue, walk over all available jobs, unlock again
     *
     * @return int
     * @throws QueueLockedException
     */
    public function process(): int
    {
        return $this->walker->walk([$this, 'processSingleJob']);
    }

    /**
     * @param JobRecord $jobRecord
     *
     * @return bool
     * @throws Throwable
     */
    public function processSingleJob(JobRecord $jobRecord): bool
    {
        $executed = false;

        $job = $jobRecord->job();
        $context = $jobRecord->context();

        try {
            $executed = $job->execute($context, $this->jobRepository, $this->logger);
            $this->logger->debug(
                sprintf(
                    "Executed Job '%s' with ID %d.",
                    $job->type(),
                    $context->id()
                ),
                (array) $context->args()
            );
        } catch (QueueRuntimeException $exception) {
            ($this->exceptionHandler)(
                new QueueRuntimeException(
                    sprintf(
                        "Failed to execute Job '%s' with ID %d",
                        $job->type(),
                        $context->id()
                    ),
                    $exception->getCode(),
                    $exception
                )
            );
        } catch (Throwable $exception) {
            /**
             * @psalm-suppress PossiblyInvalidArgument
             * getCode type
             */
            ($this->exceptionHandler)(
                new QueueRuntimeException(
                    sprintf(
                        "Unexpected error in %s. The job will now be deleted without retrying",
                        $job->type()
                    ),
                    $exception->getCode(),
                    $exception
                ),
                $this->logger,
                LogLevel::ERROR
            );
            $executed = true;
        }

        $this->jobRepository->delete($jobRecord);

        if (!$executed) {
            $this->maybeRetry($jobRecord);
        }

        return $executed;
    }

    /**
     * @param JobRecord $record
     *
     * @return JobRecord
     * @throws InvalidJobException
     */
    private function createRetry(JobRecord $record): JobRecord
    {
        return $this->recordFactory->fromData(
            $record->job()->type(),
            Context::fromArray(
                (array) $record->context()->args(),
                $record->context()->forSite(),
                $record->context()->retryCount() + 1
            )
        );
    }

    private function maybeRetry(JobRecord $record): bool
    {
        $maxRetries = $this->maxRetriesCount;

        if ($record->context()->retryCount() < $maxRetries) {
            try {
                $newRecord = $this->createRetry($record);
                $this->jobRepository->add($newRecord);
            } catch (InvalidJobException $exception) {
                ($this->exceptionHandler)($exception);

                return false;
            }

            $fails = $record->context()->retryCount() + 1;

            $this->logger->notice(
                "Job '{$record->job()->type()}' with ID {$record->context()->id()}
                failed {$fails}/{$maxRetries} times and will run again"
            );

            return true;
        }

        $this->logger->notice(
            "Job '{$record->job()->type()}' with ID {$record->context()->id()}
             has failed {$maxRetries} times and will NOT run again"
        );

        return false;
    }
}
