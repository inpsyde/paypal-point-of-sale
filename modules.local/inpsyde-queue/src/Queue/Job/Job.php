<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use Inpsyde\Queue\Exception\QueueRuntimeException;
use Psr\Log\LoggerInterface;

/**
 * Interface QueueJob
 *
 * The queue exists of entries, the QueueEntries. The execute method will execute the actions, the
 * job is supposed to do.
 *
 * @package Inpsyde\Queue\Queue
 */
interface Job
{

    /**
     * Executes the specific job.
     *
     * @param ContextInterface $context
     * @param JobRepository $repository
     * @param LoggerInterface $logger
     *
     * @return bool
     *
     * @throws QueueRuntimeException
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool;

    /**
     * If true, the repository will not add a job with the same hash() to the database twice.
     *
     * @return bool
     */
    public function isUnique(): bool;

    /**
     * The type is needed to restore the specific QueueJob from the database in the QueueJobFactory.
     *
     * @return string
     */
    public function type(): string;
}
