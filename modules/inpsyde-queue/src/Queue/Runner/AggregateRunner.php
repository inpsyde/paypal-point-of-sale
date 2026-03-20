<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Runner;

use Syde\Vendor\Zettle\Inpsyde\Queue\Processor\QueueProcessor;
/**
 * Class DelegatingRunner
 *
 * Initializes any number of child runners
 *
 * @package Inpsyde\Queue\Queue\Runner
 */
class AggregateRunner implements Runner
{
    /**
     * @var Runner[]
     */
    private $runners;
    /**
     * AggregateRunner constructor.
     *
     * @param Runner ...$runners
     */
    public function __construct(Runner ...$runners)
    {
        $this->runners = $runners;
    }
    /**
     * Initializes all child runners
     * @param QueueProcessor $queueProcessor
     */
    public function initialize(QueueProcessor $queueProcessor): void
    {
        foreach ($this->runners as $runner) {
            $runner->initialize($queueProcessor);
        }
    }
}
