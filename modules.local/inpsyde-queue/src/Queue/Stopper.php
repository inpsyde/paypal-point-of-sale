<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue;

/**
 * Interface Stopper
 *
 * An object implementing this interface can be used to stop a process
 * that would otherwise be running indefinitely by returning `true` in the `isStopped` method.
 * You can use the time laps or the number of rounds or whatever
 * you want to signal the Walker to stop its work.
 *
 * @package Inpsyde\Queue\Queue
 */
interface Stopper
{

    /**
     * This method can be used to perform initialization logic ie. storing a timestamp
     * It will be called whenever a "stoppable" process starts
     *
     * @return bool
     */
    public function start(): bool;

    /**
     * Whether or not this stopper has finished already. Expect this to be called frequently,
     * so this method should not perform expensive calculations.
     *
     * @return bool
     */
    public function isStopped(): bool;
}
