<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue;

use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Inpsyde\Modularity\Properties\LibraryProperties;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
class QueueLibrary
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var QueueModule
     */
    private $module;
    /**
     * QueueLibrary constructor.
     *
     * @param array $factories
     * @param array $extensions
     */
    public function __construct(array $factories = [], array $extensions = [])
    {
        $this->module = new QueueModule();
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
