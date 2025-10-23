<?php //phpcs:disable - There is a weird error on PHP7.4 which breaks phpcs when returning $_POST down below
declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

use Inpsyde\StateMachine\StateMachineInterface;

class GenericStateChange implements StateChange
{
    /**
     * @var string
     */
    protected $sourceState;

    /**
     * @var string
     */
    protected $newState;

    public function isPropagationStopped(): bool
    {
        return false;
    }

    public function transitionTo(string $state): bool
    {
        $this->newState = $state;

        return true;
    }

    public function targetState(): string
    {
        return $this->newState ?? $this->currentState();
    }

    public function currentState(): string
    {
        return $this->sourceState;
    }

    public function prepare(StateMachineInterface $machine)
    {
        $this->sourceState = $machine->currentState()->name();
    }
}
