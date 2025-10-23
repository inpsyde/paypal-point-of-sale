<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Dhii\Events\Event\IsPropagationStoppedCapableInterface;
use Inpsyde\StateMachine\StateMachineInterface;

interface StateChange extends IsPropagationStoppedCapableInterface
{

    public function prepare(StateMachineInterface $stateMachine);

    public function currentState(): string;

    public function transitionTo(string $state): bool;

    public function targetState(): string;
}
