<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Inpsyde\StateMachine\Transition\TransitionInterface;

interface Transitioning
{

    public function fromState(): string;

    public function transition(): TransitionInterface;
}
