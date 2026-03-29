<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

class TransitionAwareListenerProvider implements ListenerProviderInterface
{
    /**
     * @var ListenerProvider[]
     */
    private array $listeners = [];

    /**
     * @param string $state
     * @param $listener
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function listen(string $state, $listener)
    {
        if (!isset($this->listeners[$state])) {
            $this->listeners[$state] = new ListenerProvider();
        }
        $this->listeners[$state]->addListener($listener);
    }

    /**
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.InvalidGeneratorManyReturns
     */
    public function getListenersForEvent(object $event): iterable
    {
        if (!($event instanceof PostTransition || $event instanceof PreTransition)) {
            return yield from [];
        }
        $state = $event->transition()->name();
        if (!isset($this->listeners[$state])) {
            return yield from [];
        }

        yield from $this->listeners[$state]->getListenersForEvent($event);
    }
}
