<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Psr\EventDispatcher\EventDispatcherInterface;
use Syde\Vendor\Zettle\Psr\EventDispatcher\ListenerProviderInterface;
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var ListenerProviderInterface[]
     */
    private $listenerProviders;
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
