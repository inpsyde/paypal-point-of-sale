<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Inpsyde\StateMachine\StateMachineInterface;
use Psr\EventDispatcher\StoppableEventInterface;

interface StateChange extends StoppableEventInterface
{
    public function prepare(StateMachineInterface $stateMachine): void;

    public function currentState(): string;

    public function transitionTo(string $state): bool;

    public function targetState(): string;
}
