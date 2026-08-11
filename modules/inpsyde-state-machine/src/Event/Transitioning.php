<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\Transition\TransitionInterface;
interface Transitioning
{
    public function fromState(): string;
    public function transition(): TransitionInterface;
}
