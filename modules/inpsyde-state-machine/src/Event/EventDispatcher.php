<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Dhii\Events\Dispatcher\EventDispatcherInterface;
use Syde\Vendor\Zettle\Dhii\Events\Listener\ListenerProviderInterface;
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
    /**
     * @param object $event
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     *
     * @return object
     */
    public function dispatch($event)
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
