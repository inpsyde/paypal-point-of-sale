<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
use Syde\Vendor\Zettle\Psr\EventDispatcher\StoppableEventInterface;
interface StateChange extends StoppableEventInterface
{
    public function prepare(StateMachineInterface $stateMachine);
    public function currentState(): string;
    public function transitionTo(string $state): bool;
    public function targetState(): string;
}
