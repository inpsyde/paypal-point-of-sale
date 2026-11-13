<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Initializer;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\State\StateInterface;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineInterface;
/**
 * Class StateQueryInitializer
 * Initializes a state machine based on the information provided the the State objects themselves
 *
 * @package Inpsyde\StateMachine\Initializer
 */
class StateQueryInitializer implements InitializerInterface
{
    public function initialize(StateMachineInterface $stateMachine, StateInterface ...$states): StateInterface
    {
        if (empty($states)) {
            $state = $stateMachine->currentState();
        }
        foreach ($states as $state) {
            if ($state->isInitial()) {
                $stateMachine->initialize($state->name());
                return $state;
            }
        }
        return $state;
    }
}
