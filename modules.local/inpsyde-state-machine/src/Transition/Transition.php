<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Transition;

class Transition implements TransitionInterface
{

    /**
     * @var array
     */
    protected $from;

    /**
     * @var string
     */
    protected $to;

    /**
     * @var string
     */
    protected $name;

    /**
     * Transition constructor.
     *
     * @param string $name
     * @param array $from
     * @param string $to
     */
    public function __construct(
        string $name,
        array $from,
        string $to
    ) {
        $this->name = $name;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function fromStates(): array
    {
        return $this->from;
    }

    /**
     * Get State Name
     *
     * @return string
     */
    public function toState(): string
    {
        return $this->to;
    }

    public function beforeTransitionEventName(): string
    {
        return "pre-transition.{$this->name()}";
    }

    public function afterTransitionEventName(): string
    {
        return "post-transition.{$this->name()}";
    }
}
