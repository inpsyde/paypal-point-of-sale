<?php
declare(strict_types=1);
/**
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 */

namespace Syde\PayPal\PointOfSale\Test;

use Dhii\Container\CachingContainer;
use Dhii\Container\CompositeCachingServiceProvider;
use Dhii\Container\DelegatingContainer;
use Dhii\Container\ProxyContainer;
use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Syde\PayPal\PointOfSale\PluginModule;
use Interop\Container\ServiceProviderInterface;

class ModuleContainerAwareTestCase extends MonkeryTestCase
{

    private $delayedModuleContainerSetup = false;

    /**
     * @var DelegatingContainer
     */
    private $container;

    private static $providers = [];

    private $currentFactories = [];

    private $currentExtensions = [];

    public static function setUpBeforeClass(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $modules = [new PluginModule($rootDir)];
        foreach (glob($rootDir.'/modules/*/module.php') as $moduleFile) {
            $modules[] = (@require $moduleFile)();
        }
        self::$providers = array_map(function (ModuleInterface $module): ServiceProviderInterface {
            return $module->setup();
        }, $modules);
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
        $providers = self::$providers;
        $providers[] = new ServiceProvider($this->currentFactories, $this->currentExtensions);
        $provider = new CompositeCachingServiceProvider($providers);
        $proxy = new ProxyContainer();
        $container = new CachingContainer(new DelegatingContainer($provider, $proxy));
        $proxy->setInnerContainer($container);
        $this->container = $proxy;
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
