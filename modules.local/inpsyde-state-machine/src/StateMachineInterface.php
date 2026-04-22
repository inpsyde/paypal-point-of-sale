<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine;

use Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Inpsyde\StateMachine\Guard\GuardInterface;
use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\Transition\TransitionInterface;

interface StateMachineInterface
{
    public function initialize(string $initialStateName): void;

    /**
     * @param $event
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     *
     * @return mixed
     */
    public function handle($event);

    public function addTransition(TransitionInterface $transition): StateMachineInterface;

    public function addState(StateInterface $state): StateMachineInterface;

    public function addGuard(GuardInterface $state): StateMachineInterface;

    /**
     * @param $transition
     *
     * @return StateMachineInterface
     * @throws DenyTransitionException
     * phpcs:disable Syde.Functions.ReturnTypeDeclaration
     */
    public function apply($transition): StateMachineInterface;

    /**
     * @param string|TransitionInterface $transition
     *
     * @return bool
	 * phpcs:disable Syde.Functions.ReturnTypeDeclaration
     */
    public function can($transition): bool;

    public function currentState(): StateInterface;

    /**
     * @return TransitionInterface[]
     */
    public function availableTransitions(): array;

    public function initialState(): ?StateInterface;
}
