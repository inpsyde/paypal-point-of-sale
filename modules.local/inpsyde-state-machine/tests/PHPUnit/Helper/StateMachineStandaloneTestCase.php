<?php
declare(strict_types=1);
/**
 * phpcs:disable Syde.Functions.ReturnTypeDeclaration
 */

namespace Inpsyde\StateMachine\Test;

use Inpsyde\StateMachine\StateMachineLibrary;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Container\ContainerInterface;

class StateMachineStandaloneTestCase extends BrainMonkeyWpTestCase
{

    /**
     * @var ContainerInterface
     */
    private $container;

    private $currentFactories = [];

    private $currentExtensions = [];

    protected function setUp(): void
    {
        $standalone = new StateMachineLibrary($this->currentFactories, $this->currentExtensions);
        $this->container = $standalone->container();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->currentFactories = [];
        $this->currentExtensions = [];
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
