<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Runner;

use Inpsyde\Queue\Processor\QueueProcessor;

/**
 * Interface Runner
 *
 * Implementations of this interface should hook themselves into the request lifecycle
 * and ensure that the Queue starts processing.
 *
 * @package Inpsyde\Queue\Queue\Runner
 */
interface Runner
{

    /**
     * Register a method to call QueueProcessor::process() at some point.
     *
     * @param QueueProcessor $queueProcessor
     */
    public function initialize(QueueProcessor $queueProcessor): void;
}
