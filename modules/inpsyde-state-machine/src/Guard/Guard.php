<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine\Guard;

class Guard implements GuardInterface
{
    private string $transitionName;
    /**
     * @var callable
     */
    private $callables;
    private ?string $fromState = null;
    public function __construct(string $transitionName, ?string $fromState = null, callable ...$callables)
    {
        $this->transitionName = $transitionName;
        $this->callables = $callables;
        $this->fromState = $fromState;
    }
    /**
     * @inheritDoc
     */
    public function handles(string $transition, string $fromState): bool
    {
        if ($this->transitionName === $transition) {
            if (!$this->fromState) {
                return \true;
            }
            return $this->fromState === $fromState;
        }
        return \false;
    }
    public function passes(string $transition, string $fromState): bool
    {
        foreach ($this->callables as $callable) {
            if (!(bool) $callable()) {
                return \false;
            }
        }
        return \true;
    }
}
