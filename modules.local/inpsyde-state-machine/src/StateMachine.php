<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine;

use Inpsyde\StateMachine\Event\GenericPostTransition;
use Inpsyde\StateMachine\Event\GenericPreTransition;
use Inpsyde\StateMachine\Event\StateChange;
use Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Inpsyde\StateMachine\Guard\GuardInterface;
use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\Transition\TransitionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use UnexpectedValueException;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

class StateMachine implements StateMachineInterface
{
    /**
     * trigger when change state
     *
     * @var callable|null
     */
    protected $stateHandler;

    protected EventDispatcherInterface $dispatcher;

    protected StateInterface $currentState;

    /**
     * @var StateInterface[]
     */
    protected array $states = [];

    protected array $transitions = [];

    /**
     * @var GuardInterface[]
     */
    private array $guards = [];

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ?callable $updateStateHandler = null
    ) {

        $this->eventDispatcher = $eventDispatcher;
        $this->stateHandler = $updateStateHandler;
    }

    public function initialize(string $initialStateName): void
    {
        $this->currentState = $this->getState($initialStateName);
    }

    public function addTransition(TransitionInterface $transition): StateMachineInterface
    {
        $this->transitions[$transition->name()] = $transition;
        foreach ($transition->fromStates() as $stateName) {
            $this->getState($stateName)->addTransition($transition);
        }

        return $this;
    }

    public function addState(StateInterface $state): StateMachineInterface
    {
        $this->states[$state->name()] = $state;

        return $this;
    }

    /**
     * @throws DenyTransitionException
     */
    public function apply(string|TransitionInterface $transition): StateMachineInterface
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
     * @return bool
	 * phpcs:disable Syde.Functions.ReturnTypeDeclaration
     */
    public function can($transition): bool
    {
        if (!$this->currentState->can($transition)) {
            return false;
        }
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

    protected function setCurrentState(string|StateInterface $state): StateMachineInterface
    {
        if ($state instanceof StateInterface) {
            if (!in_array($state, $this->states, true)) {
                throw new UnexpectedValueException("can't find object {$state->name()} in states");
            }
        } else {
            $state = $this->getState($state);
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
     * @throws UnexpectedValueException
     */
    private function getState(string|StateInterface $state): StateInterface
    {
        $stateName = is_string($state)
            ? $state
            : $state->name();
        if (!array_key_exists($stateName, $this->states)) {
            throw new UnexpectedValueException("can't find {$stateName} in states");
        }

        return $this->states[$stateName];
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

    public function handle(object $event): void
    {
        if ($event instanceof StateChange) {
            $event->prepare($this);
        }
        $this->eventDispatcher->dispatch($event);
        if (!$event instanceof StateChange) {
            return;
        }
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
