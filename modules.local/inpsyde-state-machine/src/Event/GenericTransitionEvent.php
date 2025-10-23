<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\StateMachineInterface;
use Inpsyde\StateMachine\Transition\TransitionInterface;

class GenericTransitionEvent
{

    const PRE_TRANSITION = 'pre-transition';
    const POST_TRANSITION = 'post-transition';

    /**
     * @var TransitionInterface
     */
    protected $transition;

    /**
     * @var StateInterface
     */
    protected $fromState;

    /**
     * @var StateMachineInterface
     */
    protected $stateMachine;

    /**
     * @var StateInterface
     */
    private $toState;

    public function __construct(
        TransitionInterface $transition,
        StateInterface $fromState,
        StateInterface $toState,
        StateMachineInterface $stateMachine
    ) {
        $this->transition = $transition;
        $this->fromState = $fromState;
        $this->stateMachine = $stateMachine;
        $this->toState = $toState;
    }

    /**
     * @return TransitionInterface
     */
    public function transition(): TransitionInterface
    {
        return $this->transition;
    }

    /**
     * @return StateInterface
     */
    public function fromState(): StateInterface
    {
        return $this->fromState;
    }

    /**
     * @return StateMachineInterface
     */
    public function stateMachine(): StateMachineInterface
    {
        return $this->stateMachine;
    }

    /**
     * @return StateInterface
     */
    public function toState(): StateInterface
    {
        return $this->toState;
    }
}
