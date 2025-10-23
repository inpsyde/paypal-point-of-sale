<?php

namespace Inpsyde\Queue\Queue\Job;

use DateTime;

interface ContextInterface
{

    public function id(): int;

    /**
     * The blog ID of the site the job is supposed to run on
     * @return int
     */
    public function forSite(): int;

    /**
     * Scalar (or at least serializable) arguments for the job instance
     * @return object
     */
    public function args(): object;

    /**
     * Time of creation of the Job
     * @return DateTime
     */
    public function created(): DateTime;

    /**
     * Jobs may be re-run after failure for a set amount of times.
     * This methods returns the amount of times a Job has been retried.
     * @return int
     */
    public function retryCount(): int;

    /**
     * Returns a new instance of ContextInterface with the retry count incremented by 1
     * @return $this
     */
    public function withIncrementedRetryCount(): self;
}
