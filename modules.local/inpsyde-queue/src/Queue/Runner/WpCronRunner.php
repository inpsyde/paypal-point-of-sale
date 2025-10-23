<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Runner;

use Inpsyde\Queue\Processor\QueueProcessor;
use Throwable;

/**
 * Class WpCronRunner
 *
 * Processes the Queue in a wp-cron request that always fires whenever cron is called
 *
 * @package Inpsyde\Queue\Queue\Runner
 */
class WpCronRunner implements Runner
{

    /**
     * @var string
     */
    private $namespace;

    /**
     * WpCronRunner constructor.
     *
     * @param string $namespace
     */
    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Schedules a wp-cron event that always fires.
     *
     * @param QueueProcessor $queueProcessor
     */
    public function initialize(QueueProcessor $queueProcessor): void
    {
        $hook = "{$this->namespace}.queue.cron";

        add_action(
            $hook,
            static function () use ($queueProcessor) {
                try {
                    $queueProcessor->process();
                } catch (Throwable $exception) {
                    //silence
                }
            }
        );
        /**
         * Recurring events leave zombie data in the system if the plugin is deactivated
         * without a thorough cleanup task.
         * Re-scheduling a single event does not have this problem
         */
        if (!wp_next_scheduled($hook)) {
            wp_schedule_single_event(time() - 1, $hook);
        }
    }
}
