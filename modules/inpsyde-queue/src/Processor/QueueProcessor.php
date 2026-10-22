<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Processor;

use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\QueueLockedException;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
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
