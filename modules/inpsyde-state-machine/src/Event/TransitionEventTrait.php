<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Event;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\Transition\TransitionInterface;
trait TransitionEventTrait
{
    /**
     * @var string
     */
    private $fromState;
    /**
     * @var TransitionInterface
     */
    private $transition;
    public function __construct(string $fromState, TransitionInterface $transition)
    {
        $this->fromState = $fromState;
        $this->transition = $transition;
    }
    public function fromState(): string
    {
        return $this->fromState;
    }
    public function transition(): TransitionInterface
    {
        return $this->transition;
    }
}
