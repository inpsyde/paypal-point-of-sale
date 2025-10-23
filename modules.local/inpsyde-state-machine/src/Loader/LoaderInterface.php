<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Loader;

use Inpsyde\StateMachine\StateMachineInterface;

interface LoaderInterface
{

    public function load(StateMachineInterface $stateMachine): StateMachineInterface;
}
