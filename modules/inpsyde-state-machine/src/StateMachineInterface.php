<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\Guard\GuardInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\State\StateInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\Transition\TransitionInterface;
interface StateMachineInterface
{
    /**
     * @param string $initialStateName
     */
    public function initialize(string $initialStateName);
    /**
     * @param $event
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
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
     * @return bool
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
