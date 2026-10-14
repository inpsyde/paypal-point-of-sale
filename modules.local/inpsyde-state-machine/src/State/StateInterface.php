<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\State;

use Inpsyde\StateMachine\Transition\TransitionInterface;

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

    public function can(TransitionInterface|string $transition): bool;
}
