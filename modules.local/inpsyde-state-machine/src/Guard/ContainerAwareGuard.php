<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Guard;

use Psr\Container\ContainerInterface;

class ContainerAwareGuard implements GuardInterface
{

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(string $namespace, ContainerInterface $container)
    {
        $this->namespace = $namespace;
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function handles(string $transition, string $fromState): bool
    {
        return $this->container->has($this->key($transition, $fromState));
    }

    public function passes(string $transition, string $fromState): bool
    {
        $callback = $this->container->get($this->key($transition, $fromState));

        return (bool) $callback();
    }

    private function key(string $transition, string $fromState): string
    {
        $eventKey = "{$this->namespace}.guard.{$transition}";
        if (!empty($fromState)) {
            $eventKey .= ".from.{$fromState}";
        }

        return $eventKey;
    }
}
