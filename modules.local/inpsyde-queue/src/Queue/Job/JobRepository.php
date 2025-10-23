<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

/**
 * Interface JobRepository
 *
 * @package Inpsyde\Queue\Queue
 */
interface JobRepository
{

    /**
     * Adds a QueueJob to the repository
     *
     * @param JobRecord ...$jobRecords
     *
     * @return bool
     */
    public function add(JobRecord ...$jobRecords): bool;

    /**
     * Deletes a QueueJob from the repository
     *
     * @param JobRecord $jobRecord
     *
     * @return bool
     */
    public function delete(JobRecord $jobRecord): bool;

    /**
     * Returns a number of QueueJobs
     *
     * @param int $limit The amount of QueueJobs to fetch
     *
     * @param array $types
     *
     * @return JobRecord[]
     */
    public function fetch(int $limit = 1, array $types = []): array;

    /**
     * Delete all records in the repository
     *
     * @return bool Whether or not the operation was successful
     */
    public function flush(): bool;

    /**
     * Returns an amount of jobs
     *
     * @param array $types
     *
     * @return int
     */
    public function count(array $types = []): int;
}
