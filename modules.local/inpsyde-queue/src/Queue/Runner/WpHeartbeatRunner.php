<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Runner;

use Inpsyde\Queue\Processor\QueueProcessor;

/**
 * A Runner that initializes a WpShutdownRunner during heartbeat requests
 */
class WpHeartbeatRunner implements Runner
{

    /**
     * @var WpShutdownRunner
     */
    private $shutdownRunner;

    public function __construct(WpShutdownRunner $shutdownRunner)
    {
        $this->shutdownRunner = $shutdownRunner;
    }

    public function initialize(QueueProcessor $queueProcessor): void
    {
        $hook = function ($response) use ($queueProcessor) {
            $this->shutdownRunner->initialize($queueProcessor);

            return $response;
        };
        add_filter('heartbeat_send', $hook);
        add_filter('heartbeat_nopriv_send', $hook);
    }
}
