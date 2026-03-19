<?php

namespace Syde\Vendor\Zettle\Dhii\Events\Listener;

use Exception;
/**
 * Something that can add a listener to events.
 */
interface AddListenerCapableInterface
{
    /**
     * Adds a listener for events with the specified name.
     *
     * @param string   $name     The event name.
     * @param callable $listener The listener which will handle the event.
     * @param int      $priority The priority of the listener in the listener queue.
     *
     * @throws Exception If problem adding.
     *
     * @return void
     */
    public function addListener(string $name, callable $listener, int $priority);
}
