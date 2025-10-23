<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Loader;

use Inpsyde\StateMachine\Guard\GuardInterface;
use Inpsyde\StateMachine\Initializer\InitializerInterface;
use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\StateMachineInterface;
use Inpsyde\StateMachine\Transition\TransitionInterface;
use Psr\Container\ContainerInterface;

class ContainerLoader implements LoaderInterface
{

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var InitializerInterface
     */
    private $initializer;

    public function __construct(
        string $namespace,
        InitializerInterface $initializer,
        ContainerInterface $container
    ) {
        $this->namespace = $namespace;
        $this->initializer = $initializer;
        $this->container = $container;
    }

    public function load(StateMachineInterface $stateMachine): StateMachineInterface
    {
        $this->loadStates($stateMachine);
        $this->loadTransitions($stateMachine);
        $this->loadGuards($stateMachine);

        return $stateMachine;
    }

    private function loadStates(StateMachineInterface $stateMachine)
    {
        $key = "{$this->namespace}.states";
        if (!$this->container->has($key)) {
            return;
        }
        $states = $this->container->get($key);
        assert(is_array($states));
        foreach ($states as $state) {
            assert($state instanceof StateInterface);
            $stateMachine->addState($state);
        }
        $this->initializer->initialize($stateMachine, ...$states);
    }

    private function loadTransitions(StateMachineInterface $stateMachine)
    {
        $key = "{$this->namespace}.transitions";
        if (!$this->container->has($key)) {
            return;
        }
        $states = $this->container->get($key);
        assert(is_array($states));
        foreach ($states as $state) {
            assert($state instanceof TransitionInterface);
            $stateMachine->addTransition($state);
        }
    }

    private function loadGuards(StateMachineInterface $stateMachine)
    {
        $guards = [
            $this->container->get("inpsyde.state-machine.guards.container-aware"),
        ];
        $key = "{$this->namespace}.guards";
        if ($this->container->has($key)) {
            $guards = $this->container->get($key);
        }
        assert(is_array($guards));
        foreach ($guards as $guard) {
            assert($guard instanceof GuardInterface);
            $stateMachine->addGuard($guard);
        }
    }
}
