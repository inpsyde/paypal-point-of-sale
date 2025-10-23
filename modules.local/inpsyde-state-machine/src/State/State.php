<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\State;

use Inpsyde\StateMachine\Transition\TransitionInterface;

class State implements StateInterface
{

    protected $name;

    protected $type;

    /**
     * @var TransitionInterface[]
     */
    protected $transitions;

    public function __construct(
        string $name,
        string $type = self::TYPE_NORMAL,
        array $transitions = []
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->transitions = $transitions;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function isInitial(): bool
    {
        return $this->type === self::TYPE_INITIAL;
    }

    /**
     * @return boolean
     */
    public function isFinal(): bool
    {
        return $this->type === self::TYPE_FINAL;
    }

    /**
     * Return the available transitions
     *
     * @return array
     */
    public function transitions(): array
    {
        return $this->transitions;
    }

    /**
     * @param TransitionInterface $transition
     *
     * @return bool
     */
    public function addTransition(TransitionInterface $transition): bool
    {
        $this->transitions[$transition->name()] = $transition;

        return true;
    }

    /**
     * @param TransitionInterface $transition
     *
     * @return boolean
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function can($transition): bool
    {
        if ($this->isFinal()) {
            return false;
        }
        $name = ($transition instanceof TransitionInterface)
            ? $transition->name()
            : $transition;

        return isset($this->transitions[$name]);
    }
}
