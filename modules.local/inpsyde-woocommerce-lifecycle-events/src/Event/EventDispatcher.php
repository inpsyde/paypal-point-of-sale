<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents\Event;

use Inpsyde\WcEvents\DispatchDecider;
use Inpsyde\WcEvents\Toggle;
use Psr\Log\LoggerInterface;

/**
 * This looks like PSR-14, but currently isn't
 * TODO actually implement PSR-14 here
 */
class EventDispatcher
{

    /**
     * @var ProductEventListenerRegistry
     */
    private $listenerProvider;

    /**
     * @var Toggle
     */
    private $switch;

    /**
     * @var DispatchDecider
     */
    private $decider;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * EventDispatcher constructor.
     *
     * @param ProductEventListenerRegistry $listenerProvider
     * @param Toggle $switch
     * @param DispatchDecider $decider
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ProductEventListenerRegistry $listenerProvider,
        ?LoggerInterface $logger = null
    ) {
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
