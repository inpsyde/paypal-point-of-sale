<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine;

use Dhii\Container\CompositeCachingServiceProvider;
use Dhii\Container\DelegatingContainer;
use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\Exception\ModuleExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Class StateMachineLibrary
 * This is a helper class to make it easy to use the StateMachine as a standalone package
 * outside of the module framework. It integrates the customizations passed via constructor
 * and creates its internal container from it.
 *
 * @package Inpsyde\StateMachine
 */
class StateMachineLibrary
{

    /**
     * @var DelegatingContainer
     */
    private $container;

    /**
     * @var CompositeCachingServiceProvider
     */
    private $provider;

    /**
     * @var StateMachineModule
     */
    private $module;

    /**
     * StateMachineLibrary constructor.
     *
     * @param array $factories Overrides for dafault factories
     * @param array $extensions Extensions for default factories
     *
     * @throws ModuleExceptionInterface
     */
    public function __construct(array $factories = [], array $extensions = [])
    {
        $this->module = new StateMachineModule();
        $providers = [$this->module->setup()];
        $providers[] = new ServiceProvider($factories, $extensions);
        $this->provider = new CompositeCachingServiceProvider($providers);
        $this->container = new DelegatingContainer($this->provider);
    }

    /**
     * @throws ModuleExceptionInterface
     */
    public function initialize()
    {
        $this->module->run($this->container());
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
