<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\WcEvents\Event;

use Syde\Vendor\Zettle\Inpsyde\WcEvents\DispatchDecider;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\Toggle;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
/**
 * This looks like PSR-14, but currently isn't
 * TODO actually implement PSR-14 here
 */
class EventDispatcher
{
    private ProductEventListenerRegistry $listenerProvider;
    private Toggle $switch;
    private DispatchDecider $decider;
    private ?LoggerInterface $logger = null;
    /**
     * EventDispatcher constructor.
     *
     * @param ProductEventListenerRegistry $listenerProvider
     * @param Toggle $switch
     * @param DispatchDecider $decider
     * @param LoggerInterface|null $logger
     */
    public function __construct(ProductEventListenerRegistry $listenerProvider, ?LoggerInterface $logger = null)
    {
        $this->listenerProvider = $listenerProvider;
        $this->logger = $logger;
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
