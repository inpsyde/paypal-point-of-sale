<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Cli;

use Inpsyde\Queue\Processor\QueueProcessor;

class QueueCommand
{

    /**
     * @var QueueProcessor
     */
    private $processor;

    public function __construct(QueueProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Processes current queue items
     *
     * ## OPTIONS
     *
     * [--max-items=<value>]
     * : Whether or not to greet the person with success or error.
     * ---
     * default: -1
     * ---
     *
     * ## EXAMPLES
     *
     *     wp <namespace> queue process
     *
     * @when after_wp_load
     */
    public function process(array $args, array $assocArgs)
    {
        $this->processor->process();
    }

    /**
     * Intended to run forever, checking for new jobs to run every 2 seconds
     *
     * ## OPTIONS
     *
     * [--max-items=<value>]
     * : Whether or not to greet the person with success or error.
     * ---
     * default: -1
     * ---
     *
     * ## EXAMPLES
     *
     *     wp <namespace> queue live
     *
     * @when after_wp_load
     */
    public function live(array $args, array $assocArgs)
    {
        while (true) {
            $this->processor->process();
            sleep(2);
        }
    }
}
