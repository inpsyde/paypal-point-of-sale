<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

/**
 * Class EphemeralJobRepository
 * Stupidly simple array-based repository implementation without any sort of persistence.
 * Used for acceptance-testing
 *
 * @package Inpsyde\Queue\Queue\Job
 */
class EphemeralJobRepository implements JobRepository
{

    /**
     * @var JobRecord[]
     */
    private $repository = [];

    /**
     * @inheritDoc
     */
    public function add(JobRecord ...$jobRecords): bool
    {
        foreach ($jobRecords as $jobRecord) {
            $this->repository[spl_object_hash($jobRecord)] = $jobRecord;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(JobRecord $jobRecord): bool
    {
        unset($this->repository[spl_object_hash($jobRecord)]);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetch(int $limit = 1, array $types = []): array
    {
        if (empty($types)) {
            return array_slice($this->repository, 0, $limit);
        }
        $result = [];
        $i = 0;
        foreach ($this->repository as $record) {
            if ($i === $limit) {
                break;
            }
            if (!in_array($record->job()->type(), $types, true)) {
                continue;
            }
            $result[] = $record;
            $i++;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function count(array $types = []): int
    {
        if (empty($types)) {
            return $this->count($this->repository);
        }

        $count = 0;

        foreach ($this->repository as $record) {
            if (!in_array($record->job()->type(), $types, true)) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    public function flush(): bool
    {
        $this->repository = [];

        return true;
    }
}
