<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\WcEvents\Event;

/**
 * This looks like PSR-14, but currently isn't
 * TODO actually implement PSR-14 here
 */
class EventDispatcher
{
    private ProductEventListenerRegistry $listenerProvider;
    public function __construct(ProductEventListenerRegistry $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }
    /**
     * Retrieves listeners for the current event from the $listenerProvider
     * and calls them.
     *
     * @param ProductChangeEvent $event
     */
    public function dispatch(ProductChangeEvent $event): void
    {
        $listeners = $this->listenerProvider->getListenersForEvent($event);
        foreach ($listeners as $listener) {
            $listener($event);
        }
    }
}
