<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Dhii\Events\Event\IsPropagationStoppedCapableInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
interface StateChange extends IsPropagationStoppedCapableInterface
{
    public function prepare(StateMachineInterface $stateMachine);
    public function currentState(): string;
    public function transitionTo(string $state): bool;
    public function targetState(): string;
}
