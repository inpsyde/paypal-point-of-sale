<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Properties\LibraryProperties;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
/**
 * // phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 */
class ZettlePhpSdkLibrary
{
    private ContainerInterface $container;
    private PhpSdkModule $module;
    public function __construct(array $factories = [], array $extensions = [])
    {
        $this->module = new PhpSdkModule();
        $package = Package::new(LibraryProperties::new(__DIR__ . '/../composer.json'));
        $package->addModule($this->module);
        if ($factories !== [] || $extensions !== []) {
            $package->addModule(new class($factories, $extensions) implements ServiceModule, ExtendingModule
            {
                use ModuleClassNameIdTrait;
                private array $factories;
                private array $extensions;
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
