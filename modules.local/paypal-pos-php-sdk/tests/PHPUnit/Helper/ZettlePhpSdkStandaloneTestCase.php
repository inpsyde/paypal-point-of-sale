<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

/**
 * phpcs:disable Syde.Functions.ReturnTypeDeclaration
 */

namespace Syde\PayPal\PointOfSale\PhpSdk\Tests;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\PayPal\PointOfSale\PhpSdk\ZettlePhpSdkLibrary;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Container\ContainerInterface;
use function Brain\Monkey\Functions\when;

class ZettlePhpSdkStandaloneTestCase extends BrainMonkeyWpTestCase
{

    private $delayedModuleContainerSetup = false;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $currentFactories = [];

    /**
     * @var array
     */
    private $currentExtensions = [];

    protected function setUp(): void
    {
        when('esc_html')->returnArg();

        $this->injectFactory('paypal-pos.sync.taxation-type', function (): string {
            return TaxationType::VAT;
        });

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
        $standalone = new ZettlePhpSdkLibrary(
            $this->currentFactories,
            $this->currentExtensions
        );

        $this->container = $standalone->container();
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

    public function injectFactory(string $key, callable $factory)
    {
        $this->currentFactories[$key] = $factory;
    }

    protected function injectExtension(string $key, callable $factory)
    {
        $this->currentExtensions[$key] = $factory;
    }

    protected function single(callable $factory)
    {
        return function () use ($factory) {
            static $result;

            return $result = $result ?? $factory(...func_get_args());
        };
    }

    protected function scalar($thing)
    {
        return $this->single(
            function () use ($thing) {
                return $thing;
            }
        );
    }
}
