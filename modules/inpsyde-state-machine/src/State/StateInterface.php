<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\State;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\Transition\TransitionInterface;
interface StateInterface
{
    public const TYPE_INITIAL = 'State.Type.Initial';
    public const TYPE_NORMAL = 'State.Type.Normal';
    public const TYPE_FINAL = 'State.Type.Final';
    public function name(): string;
    public function isInitial(): bool;
    public function isFinal(): bool;
    /**
     * Return the available transitions
     *
     * @return TransitionInterface[]
     */
    public function transitions(): array;
    public function addTransition(TransitionInterface $transition): bool;
    /**
     * @param TransitionInterface|string $transition
     *
     * @return bool
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function can($transition): bool;
}
