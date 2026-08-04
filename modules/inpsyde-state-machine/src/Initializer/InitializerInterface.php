<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Initializer;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\State\StateInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
interface InitializerInterface
{
    public function initialize(StateMachineInterface $stateMachine, StateInterface ...$states): StateInterface;
}
