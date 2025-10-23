<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

class BasicJobRecord implements JobRecord
{

    /**
     * @var Job
     */
    private $job;

    /**
     * @var ContextInterface
     */
    private $context;

    public function __construct(Job $job, ContextInterface $context)
    {
        $this->job = $job;
        $this->context = $context;
    }

    public function job(): Job
    {
        return $this->job;
    }

    public function context(): ContextInterface
    {
        return $this->context;
    }
}
