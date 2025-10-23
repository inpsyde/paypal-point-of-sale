<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine;

use Dhii\Events\Dispatcher\EventDispatcherInterface;
use Inpsyde\StateMachine\Event\GenericPostTransition;
use Inpsyde\StateMachine\Event\GenericPreTransition;
use Inpsyde\StateMachine\Event\StateChange;
use Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Inpsyde\StateMachine\Guard\GuardInterface;
use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\Transition\TransitionInterface;
use UnexpectedValueException;

class StateMachine implements StateMachineInterface
{

    /**
     * trigger when change state
     *
     * @var callable|null
     */
    protected $stateHandler;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var StateInterface
     */
    protected $currentState;

    /**
     * @var array
     */
    protected $states = [];

    /**
     * @var array
     */
    protected $transitions = [];

    /**
     * @var GuardInterface[]
     */
    private $guards = [];

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ?callable $updateStateHandler = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->stateHandler = $updateStateHandler;
    }

    /**
     * @param string $initialStateName
     */
    public function initialize(string $initialStateName)
    {
        $state = $this->getState($initialStateName);
        if ($state === null) {
            throw new UnexpectedValueException("can't find {$initialStateName} in states");
        }

        $this->currentState = $state;
    }

    /**
     * @param TransitionInterface $transition
     *
     * @return mixed
     */
    public function addTransition(TransitionInterface $transition): StateMachineInterface
    {
        $this->transitions[$transition->name()] = $transition;
        foreach ($transition->fromStates() as $stateName) {
            $state = $this->getState($stateName);
            if ($state) {
                $state->addTransition($transition);
            }
        }

        return $this;
    }

    /**
     * @param StateInterface $state
     *
     * @return mixed
     */
    public function addState(StateInterface $state): StateMachineInterface
    {
        $this->states[$state->name()] = $state;

        return $this;
    }

    /**
     * @param string $transition
     *
     * @return mixed
     * @throws DenyTransitionException
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function apply($transition): StateMachineInterface
    {
        if (is_string($transition)) {
            if (!isset($this->transitions[$transition])) {
                throw new DenyTransitionException("Transition $transition not found");
            }
            $transition = $this->transitions[$transition];
        }

        if (!$this->can($transition)) {
            throw new DenyTransitionException(
                sprintf(
                    "Current State %s can't make Transition %s",
                    $this->currentState->name(),
                    $transition instanceof TransitionInterface
                        ? $transition->name()
                        : $transition
                )
            );
        }

        $oldState = $this->currentState->name();
        $newState = $this->getState($transition->toState());

        $this->eventDispatcher->dispatch(
            new GenericPreTransition($oldState, $transition)
        );

        $this->setCurrentState($newState);

        $this->eventDispatcher->dispatch(
            new GenericPostTransition($oldState, $transition)
        );

        return $this;
    }

    /**
     * @param string $transition
     *
     * @return TransitionInterface
     */
    private function transition(string $transition): TransitionInterface
    {
        if (!isset($this->transitions[$transition])) {
            throw new UnexpectedValueException("Transition $transition not found");
        }

        return $this->transitions[$transition];
    }

    /**
     * @param string|TransitionInterface $transition
     *
     * @return boolean
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function can($transition): bool
    {
        if (!$this->currentState->can($transition)) {
            return false;
        }
        //phpcs:disable Inpsyde.CodeQuality.NoElse
        if (is_string($transition)) {
            $transitionName = $transition;
            $transition = $this->transition($transition);
        } else {
            assert($transition instanceof TransitionInterface);
            $transitionName = $transition->name();
        }

        $guards = [];
        foreach ($this->guards as $guard) {
            if ($guard->handles($transitionName, $this->currentState->name())) {
                $guards[] = $guard;
            }
        }
        foreach ($guards as $guard) {
            if (!$guard->passes($transitionName, $this->currentState->name())) {
                return false;
            }
        }
        try {
            $this->getState($transition->toState());
        } catch (UnexpectedValueException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return StateInterface
     */
    public function currentState(): StateInterface
    {
        return $this->currentState;
    }

    /**
     * @param $state
     *
     * @return StateMachineInterface
     */
    protected function setCurrentState($state): StateMachineInterface
    {
        if ($state instanceof StateInterface) {
            if (!in_array($state, $this->states, true)) {
                throw new UnexpectedValueException("can't find object {$state->name()} in states");
            }
        } elseif (is_string($state)) {
            $state = $this->getState($state);
            if ($state === null) {
                throw new UnexpectedValueException("can't find {$state} in states");
            }
        } else {
            throw new UnexpectedValueException("Method setCurrentState only accept string or StateInterface.");
        }

        $this->currentState = $state;
        // phpcs:disable NeutronStandard.Functions.DisallowCallUserFunc.CallUserFunc
        $this->stateHandler && call_user_func_array($this->stateHandler, [$state]);

        return $this;
    }

    /**
     * @return TransitionInterface[]
     */
    public function availableTransitions(): array
    {
        return $this->currentState->transitions();
    }

    /**
     * @return array
     */
    public function transitions(): array
    {
        return $this->transitions;
    }

    /**
     * @param string|StateInterface $state
     *
     * @return boolean
     * @throws UnexpectedValueException
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    private function getState($state): StateInterface
    {
        $stateName = is_string($state)
            ? $state
            : $state->name();
        if (!array_key_exists($stateName, $this->states)) {
            throw new UnexpectedValueException("can't find {$stateName} in states");
        }

        return $this->states[$state];
    }

    public function initialState(): ?StateInterface
    {
        /** @var StateInterface $state */
        foreach ($this->states as $state) {
            if ($state->isInitial()) {
                return $state;
            }
        }

        return null;
    }

    public function addGuard(GuardInterface $guard): StateMachineInterface
    {
        $this->guards[] = $guard;

        return $this;
    }

    public function handle($event)
    {
        if ($event instanceof StateChange) {
            $event->prepare($this);
        }
        $this->eventDispatcher->dispatch($event);
        $targetState = $event->targetState();
        if ($targetState === $this->currentState()->name()) {
            return;
        }
        $transition = array_filter(
            $this->availableTransitions(),
            static function (TransitionInterface $transition) use ($targetState): bool {
                return $transition->toState() === $targetState;
            }
        );
        if (empty($transition)) {
            return;
        }
        $this->apply(current($transition));
    }
}
