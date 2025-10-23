<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Initializer;

use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\StateMachineInterface;

interface InitializerInterface
{

    public function initialize(
        StateMachineInterface $stateMachine,
        StateInterface ...$states
    ): StateInterface;
}
