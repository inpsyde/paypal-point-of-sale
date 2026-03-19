<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Dhii\Events\Event\IsPropagationStoppedCapableInterface;
use Syde\Vendor\Zettle\Dhii\Events\Listener\ListenerProviderInterface;
use Traversable;
class ListenerProvider implements ListenerProviderInterface
{
    use ParameterDeriverTrait;
    private $listeners;
    public function __construct(callable ...$listeners)
    {
        $this->listeners = $listeners;
    }
    public function addListener(callable $listener)
    {
        $this->listeners[] = $listener;
    }
    /**
     * @param object $event
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     *
     * @return Traversable
     */
    public function getListenersForEvent($event): Traversable
    {
        $eventType = get_class($event);
        $extends = class_parents($event);
        $implements = class_implements($event);
        foreach ($this->listeners as $listener) {
            if ($event instanceof IsPropagationStoppedCapableInterface && $event->isPropagationStopped()) {
                break;
            }
            $type = $this->getParameterType($listener);
            if ($type === $eventType) {
                yield $listener;
                continue;
            }
            if (isset($implements[$type])) {
                yield $listener;
                continue;
            }
            if (isset($extends[$type])) {
                yield $listener;
                continue;
            }
        }
        return yield from [];
    }
}
