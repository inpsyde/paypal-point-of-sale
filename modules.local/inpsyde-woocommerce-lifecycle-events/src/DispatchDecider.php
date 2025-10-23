<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents;

use Inpsyde\WcEvents\Event\ProductChangeEvent;

/**
 * Similar in intention to Toggle, but this class allows preventing dispatch on a per-event basis.
 * Has a method to add deciders from outside
 */
class DispatchDecider
{

    private $deciders;

    /**
     * DispatchDecider constructor.
     * TODO Discuss if there should be a discrete DeciderInterface instead of callable
     *
     * @param array<callable(ProductChangeEvent):bool> ...$deciders
     */
    public function __construct(callable ...$deciders)
    {
        $this->deciders = $deciders;
    }

    /**
     * Calls all deciders and returns true if all deciders returned true
     *
     * @param ProductChangeEvent $event
     *
     * @return bool
     */
    public function isEventDispatchable(ProductChangeEvent $event): bool
    {
        foreach ($this->deciders as $decider) {
            if (!$decider($event)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adds a new decider. Deciders are a callable that accepts a ProductChangeEvent
     * as its only parameter and returns boolean
     * @param callable(ProductChangeEvent):bool $decider
     */
    public function addDecider(callable $decider)
    {
        $this->deciders[] = $decider;
    }
}
