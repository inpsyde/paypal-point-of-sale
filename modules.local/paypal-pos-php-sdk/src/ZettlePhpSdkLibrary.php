<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk;

use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\Modularity\Package;
use Inpsyde\Modularity\Properties\LibraryProperties;
use Psr\Container\ContainerInterface;

/**
 * // phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 */
class ZettlePhpSdkLibrary
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var PhpSdkModule
     */
    private $module;

    public function __construct(array $factories = [], array $extensions = [])
    {
        $this->module = new PhpSdkModule();
        $package = Package::new(LibraryProperties::new(__DIR__ . '/../composer.json'));
        $package->addModule($this->module);

        if ($factories !== [] || $extensions !== []) {
            $package->addModule(
                new class ($factories, $extensions) implements ServiceModule, ExtendingModule {
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
                }
            );
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
