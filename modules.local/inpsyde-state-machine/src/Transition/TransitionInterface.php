<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Transition;

interface TransitionInterface
{

    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return string[]
     */
    public function fromStates(): array;

    /**
     * Get State Name
     *
     * @return string
     */
    public function toState(): string;

    public function beforeTransitionEventName(): string;

    public function afterTransitionEventName(): string;
}
