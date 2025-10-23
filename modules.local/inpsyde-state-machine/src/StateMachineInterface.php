<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine;

use Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Inpsyde\StateMachine\Guard\GuardInterface;
use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\Transition\TransitionInterface;

interface StateMachineInterface
{

    /**
     * @param string $initialStateName
     */
    public function initialize(string $initialStateName);

    /**
     * @param $event
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     *
     * @return mixed
     */
    public function handle($event);

    /**
     * @param TransitionInterface $transition
     *
     * @return mixed
     */
    public function addTransition(TransitionInterface $transition): StateMachineInterface;

    public function addState(StateInterface $state): StateMachineInterface;

    public function addGuard(GuardInterface $state): StateMachineInterface;

    /**
     * @param $transition
     *
     * @return StateMachineInterface
     * @throws DenyTransitionException
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function apply($transition): StateMachineInterface;

    /**
     * @param string|TransitionInterface $transition
     *
     * @return boolean
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function can($transition): bool;

    public function currentState(): StateInterface;

    /**
     * @return TransitionInterface[]
     */
    public function availableTransitions(): array;

    public function initialState(): ?StateInterface;
}
