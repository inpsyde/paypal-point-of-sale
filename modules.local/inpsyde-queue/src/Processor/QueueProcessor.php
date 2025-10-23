<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Processor;

use Inpsyde\Queue\Exception\QueueException;
use Inpsyde\Queue\Exception\QueueLockedException;
use Inpsyde\Queue\Queue\Job\JobRepository;

interface QueueProcessor
{
    /**
     * Return the JobRepository
     *
     * @return JobRepository
     */
    public function repository(): JobRepository;

    /**
     * Return the amount of processed Jobs
     * @throws QueueLockedException
     * @return int
     */
    public function process(): int;
}
