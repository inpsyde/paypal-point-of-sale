<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\State;

use Inpsyde\StateMachine\Transition\TransitionInterface;

interface StateInterface
{
    const TYPE_INITIAL = 'State.Type.Initial';
    const TYPE_NORMAL = 'State.Type.Normal';
    const TYPE_FINAL = 'State.Type.Final';

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
