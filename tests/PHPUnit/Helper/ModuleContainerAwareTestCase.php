<?php
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\Modularity\Package;
use Inpsyde\Modularity\Properties\LibraryProperties;
use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\PluginModule;

class ModuleContainerAwareTestCase extends MonkeryTestCase
{

    private ?ContainerInterface $container = null;

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

    /**
     * (Re)builds the module container from the factories/extensions injected so
     * far. The container is built lazily on the first get() call, so tests only
     * need to call this explicitly when they want to force a rebuild after
     * injecting additional per-test services.
     */
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
        if ($this->container === null) {
            $this->setupModuleContainer();
        }

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
