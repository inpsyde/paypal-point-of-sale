<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Psr\EventDispatcher\ListenerProviderInterface;
class StateAwareListenerProvider implements ListenerProviderInterface
{
    /**
     * @var ListenerProvider[]
     */
    private array $listeners = [];
    public function listen(string $state, callable $listener)
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
        if (!$event instanceof StateChange) {
            return yield from [];
        }
        $state = $event->targetState();
        if (!isset($this->listeners[$state])) {
            return yield from [];
        }
        yield from $this->listeners[$state]->getListenersForEvent($event);
    }
}
