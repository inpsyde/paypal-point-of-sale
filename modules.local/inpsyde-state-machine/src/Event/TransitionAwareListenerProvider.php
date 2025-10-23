<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Dhii\Events\Listener\ListenerProviderInterface;
use Traversable;

class TransitionAwareListenerProvider implements ListenerProviderInterface
{

    /**
     * @var ListenerProvider[]
     */
    private $listeners = [];

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
     * @param object $event
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.InvalidGeneratorManyReturns
     * @return Traversable
     */
    public function getListenersForEvent($event): Traversable
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
