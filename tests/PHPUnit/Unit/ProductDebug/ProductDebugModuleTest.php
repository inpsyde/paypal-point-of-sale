<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Tests\Unit\ProductDebug;

use Brain\Monkey\Actions;
use Mockery;
use Psr\Container\ContainerInterface;
use stdClass;
use Syde\PayPal\PointOfSale\ProductDebug\ProductDebugModule;
use Syde\PayPal\PointOfSale\Test\MonkeryTestCase;

/**
 * Runs in a separate process because it defines the WP_CLI constant and aliases the WP_CLI class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ProductDebugModuleTest extends MonkeryTestCase
{
    private const COMMAND_SERVICE = 'paypal-pos.product.debug.cli.products';

    /** @var list<string> */
    private array $resolvedServices = [];

    /** @var list<array{0: string, 1: object}> */
    private array $registeredCommands = [];

    private object $command;

    private ?\Closure $initCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('WP_CLI')) {
            define('WP_CLI', true);
        }

        $this->command = new stdClass();

        $registeredCommands = &$this->registeredCommands;
        Mockery::mock('alias:WP_CLI')
            ->shouldReceive('add_command')
            ->andReturnUsing(static function (string $name, object $command) use (&$registeredCommands): void {
                $registeredCommands[] = [$name, $command];
            });

        $initCallback = &$this->initCallback;
        Actions\expectAdded('init')->whenHappen(
            static function (callable $callback) use (&$initCallback): void {
                $initCallback = $callback;
            }
        );
    }

    /**
     * GIVEN the plugin runs under WP-CLI during plugins_loaded
     * WHEN the product debug module runs
     * THEN the CLI command service is not resolved and no command is registered yet
     * AND the registration is deferred to the init action
     */
    public function testRunDefersCommandRegistrationUntilInitWhenRunningUnderWpCli(): void
    {
        $result = (new ProductDebugModule())->run($this->container());

        static::assertTrue($result);
        static::assertNotContains(self::COMMAND_SERVICE, $this->resolvedServices);
        static::assertSame([], $this->registeredCommands);
        static::assertIsCallable($this->initCallback);
    }

    /**
     * GIVEN the module ran under WP-CLI and deferred the command registration to init
     * WHEN WordPress fires the init action
     * THEN the "zettle products" command is registered with the resolved command service
     */
    public function testInitRegistersZettleProductsCommandWithResolvedService(): void
    {
        (new ProductDebugModule())->run($this->container());
        static::assertIsCallable($this->initCallback);

        ($this->initCallback)();

        static::assertSame([['zettle products', $this->command]], $this->registeredCommands);
    }

    private function container(): ContainerInterface
    {
        $resolved = &$this->resolvedServices;
        $command = $this->command;

        $container = Mockery::mock(ContainerInterface::class);
        $container->allows('get')->andReturnUsing(
            static function (string $id) use (&$resolved, $command): object {
                $resolved[] = $id;

                return $id === self::COMMAND_SERVICE ? $command : new stdClass();
            }
        );

        return $container;
    }
}
