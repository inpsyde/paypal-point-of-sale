<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\State;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\Transition\TransitionInterface;
class State implements StateInterface
{
    protected string $name;
    protected string $type;
    /**
     * @var TransitionInterface[]
     */
    protected array $transitions;
    public function __construct(string $name, string $type = self::TYPE_NORMAL, array $transitions = [])
    {
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
     * @return bool
     */
    public function isInitial(): bool
    {
        return $this->type === self::TYPE_INITIAL;
    }
    /**
     * @return bool
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
    public function addTransition(TransitionInterface $transition): bool
    {
        $this->transitions[$transition->name()] = $transition;
        return \true;
    }
    public function can(TransitionInterface|string $transition): bool
    {
        if ($this->isFinal()) {
            return \false;
        }
        $name = $transition instanceof TransitionInterface ? $transition->name() : $transition;
        return isset($this->transitions[$name]);
    }
}
