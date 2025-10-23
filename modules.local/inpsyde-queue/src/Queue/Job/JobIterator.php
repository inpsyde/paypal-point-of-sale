<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use Iterator;

/**
 * Class QueueJobIterator
 *
 * @package Inpsyde\Queue\Queue
 */
class JobIterator implements Iterator
{
    /**
     * Used to fetch a batch of new jobs once we're done with the current one
     * @var JobRepository
     */
    private $repo;

    /**
     * @var array
     */
    private $types;

    /**
     * Current batch of jobs to iterate over
     * @var array
     */
    private $jobs = [];

    /**
     * @var Job
     */
    private $current;

    /**
     * @var int
     */
    private $idx = 0;

    /**
     * @var callable
     */
    private $sortCallback;

    /**
     * QueueJobIterator constructor.
     *
     * @param JobRepository $repo
     * @param array $types
     * @param callable|null $sortCallback
     */
    public function __construct(JobRepository $repo, array $types = [], ?callable $sortCallback = null)
    {
        $this->repo = $repo;
        $this->types = $types;
        $this->sortCallback = $sortCallback
            ?? static function (
                JobRecord $firstRecord,
                JobRecord $secondRecord
            ): int {
                /**
                 * Sorting the jobs by site ID can reduce switch_to_blog() actions.
                 */
                return $firstRecord->context()->forSite() <=> $secondRecord->context()->forSite();
            };
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->idx;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid(): bool
    {
        return ($this->current !== null && $this->current instanceof JobRecord);
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current(): JobRecord
    {
        return $this->current;
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next(): void
    {
        if (!count($this->jobs)) {
            $this->jobs = $this->repo->fetch(10, $this->types);

            if (count($this->jobs) > 1) {
                usort($this->jobs, $this->sortCallback);
            }
        }

        $this->current = array_shift($this->jobs);
        $this->idx++;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind(): void
    {
        $this->idx = 0;
        $this->jobs = [];
        $this->next();
    }
}
