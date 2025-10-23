<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Guard;

interface GuardInterface
{

    /**
     * @param string $transition
     *
     * @param string $fromState
     *
     * @return bool
     */
    public function handles(string $transition, string $fromState): bool;

    public function passes(string $transition, string $fromState): bool;
}
