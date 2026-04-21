<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var ListenerProviderInterface[]
     */
    private array $listenerProviders;

    public function __construct(ListenerProviderInterface ...$listenerProviders)
    {
        $this->listenerProviders = $listenerProviders;
    }

    public function dispatch(object $event): object
    {
        foreach ($this->listenerProviders as $listenerProvider) {
            $listeners = $listenerProvider->getListenersForEvent($event);
            foreach ($listeners as $listener) {
                $listener($event);
            }
        }
        return $event;
    }
}
