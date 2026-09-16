<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\State\StateInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\Transition\TransitionInterface;
class GenericTransitionEvent
{
    public const PRE_TRANSITION = 'pre-transition';
    public const POST_TRANSITION = 'post-transition';
    protected TransitionInterface $transition;
    protected StateInterface $fromState;
    protected StateMachineInterface $stateMachine;
    private StateInterface $toState;
    public function __construct(TransitionInterface $transition, StateInterface $fromState, StateInterface $toState, StateMachineInterface $stateMachine)
    {
        $this->transition = $transition;
        $this->fromState = $fromState;
        $this->stateMachine = $stateMachine;
        $this->toState = $toState;
    }
    public function transition(): TransitionInterface
    {
        return $this->transition;
    }
    public function fromState(): StateInterface
    {
        return $this->fromState;
    }
    public function stateMachine(): StateMachineInterface
    {
        return $this->stateMachine;
    }
    public function toState(): StateInterface
    {
        return $this->toState;
    }
}
