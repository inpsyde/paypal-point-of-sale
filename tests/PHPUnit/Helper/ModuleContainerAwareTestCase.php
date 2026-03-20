<?php
declare(strict_types=1);
/**
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 */

namespace Syde\PayPal\PointOfSale\Test;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Properties\LibraryProperties;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\PluginModule;

class ModuleContainerAwareTestCase extends MonkeryTestCase
{

    private $delayedModuleContainerSetup = false;

    /**
     * @var ContainerInterface
     */
    private $container;

    private static $modules = [];

    private $currentFactories = [];

    private $currentExtensions = [];

    public static function setUpBeforeClass(): void
    {
        $rootDir = dirname(__DIR__, 3);
        self::$modules = [new PluginModule()];
        foreach (glob($rootDir.'/modules/*/module.php') as $moduleFile) {
            $module = (@require $moduleFile)();
            if ($module !== null) {
                self::$modules[] = $module;
            }
        }
    }

    protected function setUp(): void
    {
        if (!$this->delayedModuleContainerSetup) {
            $this->setupModuleContainer();
        }
        parent::setUp();
    }

    /**
     * If this flag is set during setUp() in a child class,
     * the container won't be initialized in setUp.
     * This allows you to inject services in individual tests, but you will have to call
     * setupModuleContainer() yourself in each test
     */
    protected function delayModuleContainerSetup()
    {
        $this->delayedModuleContainerSetup = true;
    }

    protected function setupModuleContainer()
    {
        $rootDir = dirname(__DIR__, 3);
        $properties = LibraryProperties::new($rootDir.'/composer.json');
        $package = Package::new($properties);

        foreach (self::$modules as $module) {
            $package->addModule($module);
        }

        $factories = $this->currentFactories;
        $extensions = $this->currentExtensions;
        if (!empty($factories) || !empty($extensions)) {
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

    protected function tearDown(): void
    {
        unset($this->currentExtensions);
        unset($this->currentFactories);
        unset($this->container);
        parent::tearDown();
    }

    protected function get(string $key)
    {
        return $this->container->get($key);
    }

    protected function injectFactory(string $key, callable $factory)
    {
        $this->currentFactories[$key] = $factory;
    }

    protected function injectExtension(string $key, callable $factory)
    {
        $this->currentExtensions[$key] = $factory;
    }

    protected function scalar($thing)
    {
        return function () use ($thing) {
            return $thing;
        };
    }
}
