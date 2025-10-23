<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents;

/**
 * This class can be used to prevent dispatching events.
 * Use it if you need to silently update products, for example when processing webhooks
 * in a setup where you sync products to a remote service on every change
 */
class Toggle
{

    private $state = true;

    /**
     * Enables dispatching of event. This is the default state
     */
    public function enable(): void
    {
        $this->state = true;
    }

    /**
     * Returns true if events should be dispatched
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->state;
    }

    /**
     * Prevents events from being dispatched
     */
    public function disable(): void
    {
        $this->state = false;
    }
}
