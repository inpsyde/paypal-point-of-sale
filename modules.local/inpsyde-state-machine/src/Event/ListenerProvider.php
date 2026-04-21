<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class ListenerProvider implements ListenerProviderInterface
{
    use ParameterDeriverTrait;

    /** @var array<callable> */
    private array $listeners;

    public function __construct(callable ...$listeners)
    {
        $this->listeners = $listeners;
    }

    public function addListener(callable $listener)
    {
        $this->listeners[] = $listener;
    }

    public function getListenersForEvent(object $event): iterable
    {
        $eventType = get_class($event);
        $extends = class_parents($event);
        $implements = class_implements($event);
        foreach ($this->listeners as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
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
