<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Loader;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
interface LoaderInterface
{
    public function load(StateMachineInterface $stateMachine): StateMachineInterface;
}
