<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\StateMachine;

use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Properties\LibraryProperties;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
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
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var StateMachineModule
     */
    private $module;
    /**
     * StateMachineLibrary constructor.
     *
     * @param array $factories Overrides for dafault factories
     * @param array $extensions Extensions for default factories
     */
    public function __construct(array $factories = [], array $extensions = [])
    {
        $this->module = new StateMachineModule();
        $package = Package::new(LibraryProperties::new(__DIR__ . '/../composer.json'));
        $package->addModule($this->module);
        if ($factories !== [] || $extensions !== []) {
            $package->addModule(new class($factories, $extensions) implements ServiceModule, ExtendingModule
            {
                use ModuleClassNameIdTrait;
                /**
                 * @var array
                 */
                private $factories;
                /**
                 * @var array
                 */
                private $extensions;
                public function __construct(array $factories, array $extensions)
                {
                    $this->factories = $factories;
                    $this->extensions = $extensions;
                }
                public function services(): array
                {
                    return $this->factories;
                }
                public function extensions(): array
                {
                    return $this->extensions;
                }
            });
        }
        $package->build();
        $this->container = $package->container();
    }
    public function initialize()
    {
        $this->module->run($this->container());
    }
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
