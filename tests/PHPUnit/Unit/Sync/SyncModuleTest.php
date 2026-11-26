<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Tests\Unit\Sync;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Mockery;
use Psr\Container\ContainerInterface;
use stdClass;
use Syde\PayPal\PointOfSale\Sync\SyncModule;
use Syde\PayPal\PointOfSale\Test\MonkeryTestCase;

/**
 * Runs in a separate process because it aliases the WP_CLI class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SyncModuleTest extends MonkeryTestCase
{
    private const COMMAND_SERVICES = [
        'zettle sync' => 'paypal-pos.sync.cli.sync-product',
        'zettle unlink' => 'paypal-pos.sync.cli.unlink-product',
        'zettle reset' => 'paypal-pos.sync.cli.reset',
        'zettle export' => 'paypal-pos.sync.cli.export',
        'zettle exclude' => 'paypal-pos.sync.cli.exclude',
    ];

    /** @var list<string> */
    private array $resolvedServices = [];

    /** @var list<array{0: string, 1: object}> */
    private array $registeredCommands = [];

    /** @var array<string, object> */
    private array $commands = [];

    private ?\Closure $cliInitCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('is_admin')->justReturn(false);

        foreach (self::COMMAND_SERVICES as $service) {
            $this->commands[$service] = new stdClass();
        }

        $registeredCommands = &$this->registeredCommands;
        Mockery::mock('alias:WP_CLI')
            ->shouldReceive('add_command')
            ->andReturnUsing(static function (string $name, object $command) use (&$registeredCommands): void {
                $registeredCommands[] = [$name, $command];
            });

        $cliInitCallback = &$this->cliInitCallback;
        Actions\expectAdded('cli_init')->whenHappen(
            static function (callable $callback) use (&$cliInitCallback): void {
                $cliInitCallback = $callback;
            }
        );
    }

    /**
     * GIVEN the plugin loads its modules during plugins_loaded
     * WHEN the sync module runs
     * THEN none of the CLI command services is resolved and no command is registered yet
     * AND the registration is deferred to the cli_init action
     */
    public function testRunDefersCommandRegistrationUntilCliInit(): void
    {
        $result = (new SyncModule())->run($this->container());

        static::assertTrue($result);
        static::assertSame([], array_intersect(self::COMMAND_SERVICES, $this->resolvedServices));
        static::assertSame([], $this->registeredCommands);
        static::assertIsCallable($this->cliInitCallback);
    }

    /**
     * GIVEN the module deferred the command registration to cli_init
     * WHEN WP-CLI fires the cli_init action
     * THEN the sync, unlink, reset, export and exclude commands are registered with their services
     */
    public function testCliInitRegistersAllZettleCommandsWithResolvedServices(): void
    {
        (new SyncModule())->run($this->container());
        static::assertIsCallable($this->cliInitCallback);

        ($this->cliInitCallback)();

        $expected = [];
        foreach (self::COMMAND_SERVICES as $name => $service) {
            $expected[] = [$name, $this->commands[$service]];
        }
        static::assertSame($expected, $this->registeredCommands);
    }

    private function container(): ContainerInterface
    {
        $resolved = &$this->resolvedServices;
        $commands = $this->commands;

        $container = Mockery::mock(ContainerInterface::class);
        $container->allows('get')->andReturnUsing(
            static function (string $id) use (&$resolved, $commands): object {
                $resolved[] = $id;

                return $commands[$id] ?? new stdClass();
            }
        );

        return $container;
    }
}
