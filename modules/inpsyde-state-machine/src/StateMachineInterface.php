<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\Guard\GuardInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\State\StateInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\Transition\TransitionInterface;
interface StateMachineInterface
{
    public function initialize(string $initialStateName): void;
    public function handle(object $event): void;
    public function addTransition(TransitionInterface $transition): StateMachineInterface;
    public function addState(StateInterface $state): StateMachineInterface;
    public function addGuard(GuardInterface $state): StateMachineInterface;
    /**
     * @throws DenyTransitionException
     */
    public function apply(string|TransitionInterface $transition): StateMachineInterface;
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
