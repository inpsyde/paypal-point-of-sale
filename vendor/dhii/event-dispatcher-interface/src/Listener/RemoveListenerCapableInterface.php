<?php

namespace Syde\Vendor\Zettle\Dhii\Events\Listener;

/**
 * Something that can unregister an event listener.
 */
interface RemoveListenerCapableInterface
{
    /**
     * Unregisters an event listener from an event.
     *
     * @param string   $name     The event name.
     * @param callable $listener The listener to unregister.
     * @param int      $priority The priority of the listener.
     *
     * @return void
     */
    public function removeListener(string $name, callable $listener, int $priority);
}
