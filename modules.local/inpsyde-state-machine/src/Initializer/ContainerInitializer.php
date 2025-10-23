<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Initializer;

use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\StateMachineInterface;
use Psr\Container\ContainerInterface;

/**
 * Class ContainerInitializer
 *
 * Queries a Container for the current state.
 * Falls back to its child initializer on failure
 *
 * @package Inpsyde\StateMachine\Initializer
 */
class ContainerInitializer implements InitializerInterface
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
    private $childInitializer;

    public function __construct(
        string $namespace,
        ContainerInterface $container,
        InitializerInterface $childInitializer
    ) {
        $this->namespace = $namespace;
        $this->container = $container;
        $this->childInitializer = $childInitializer;
    }

    public function initialize(
        StateMachineInterface $stateMachine,
        StateInterface ...$states
    ): StateInterface {
        $key = "{$this->namespace}.initial-state";
        if (!$this->container->has($key)) {
            goto child;
        }
        $currentState = $this->container->get($key);
        if (empty($currentState)) {
            goto child;
        }
        foreach ($states as $state) {
            if ($state->name() === $currentState) {
                $stateMachine->initialize($state->name());

                return $state;
            }
        }
        child:

        return $this->childInitializer->initialize($stateMachine, ...$states);
    }
}
