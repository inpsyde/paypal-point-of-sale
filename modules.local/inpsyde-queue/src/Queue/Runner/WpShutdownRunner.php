<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Runner;

use Exception;
use Inpsyde\Queue\Processor\QueueProcessor;

/**
 * Class WpShutdownRunner
 *
 * Hooks into 'shutdown' to process the queue as late as possible in the current request.
 * This of course is no longer "background" processing as it still blocks the current request,
 * so use with caution.
 *
 * Still, since WP calls this action via 'register_shutdown_function' we at least
 * know that any HTML has already been sent to the user
 *
 * @package Inpsyde\Queue\Queue\Runner
 */
class WpShutdownRunner implements Runner
{

    private $called = false;

    /**
     * Hook the QueueProcessor into the shutdown action.
     * Ensures that the Runner is only being onvoked once even if the hook is called multiple times.
     *
     * @param QueueProcessor $queueProcessor
     */
    public function initialize(QueueProcessor $queueProcessor): void
    {
        add_action(
            'shutdown',
            function () use ($queueProcessor) {
                if ($this->called) {
                    return;
                }
                $this->called = true;
                try {
                    $queueProcessor->process();
                } catch (Exception $exception) {
                    //silence
                }
            },
            -100
        );
    }
}
