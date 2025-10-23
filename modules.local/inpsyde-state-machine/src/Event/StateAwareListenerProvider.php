<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Dhii\Events\Listener\ListenerProviderInterface;
use Traversable;

class StateAwareListenerProvider implements ListenerProviderInterface
{

    /**
     * @var ListenerProvider[]
     */
    private $listeners = [];

    public function listen(string $state, callable $listener)
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
        if (!($event instanceof StateChange)) {
            return yield from [];
        }
        $state = $event->targetState();
        if (!isset($this->listeners[$state])) {
            return yield from [];
        }

        yield from $this->listeners[$state]->getListenersForEvent($event);
    }
}
