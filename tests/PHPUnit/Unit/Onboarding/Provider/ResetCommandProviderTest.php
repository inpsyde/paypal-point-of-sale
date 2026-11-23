<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Tests\Unit\Onboarding\Provider;

use Brain\Monkey\Actions;
use Mockery;
use Psr\Container\ContainerInterface;
use stdClass;
use Syde\PayPal\PointOfSale\Onboarding\Provider\ResetCommandProvider;
use Syde\PayPal\PointOfSale\Test\MonkeryTestCase;

/**
 * Runs in a separate process because it aliases the WP_CLI class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ResetCommandProviderTest extends MonkeryTestCase
{
    private const COMMAND_SERVICE = 'paypal-pos.onboarding.cli.reset.onboarding';

    /** @var list<string> */
    private array $resolvedServices = [];

    /** @var list<array{0: string, 1: object}> */
    private array $registeredCommands = [];

    private object $command;

    private ?\Closure $cliInitCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new stdClass();

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
     * GIVEN the plugin loads its providers during plugins_loaded
     * WHEN the reset command provider boots
     * THEN the command service is not resolved and no command is registered yet
     * AND the registration is deferred to the cli_init action
     */
    public function testBootDefersCommandRegistrationUntilCliInit(): void
    {
        $result = (new ResetCommandProvider())->boot($this->container());

        static::assertTrue($result);
        static::assertSame([], $this->resolvedServices);
        static::assertSame([], $this->registeredCommands);
        static::assertIsCallable($this->cliInitCallback);
    }

    /**
     * GIVEN the provider deferred the command registration to cli_init
     * WHEN WP-CLI fires the cli_init action
     * THEN the "zettle reset onboarding" command is registered with the resolved command service
     */
    public function testCliInitRegistersZettleResetOnboardingCommandWithResolvedService(): void
    {
        (new ResetCommandProvider())->boot($this->container());
        static::assertIsCallable($this->cliInitCallback);

        ($this->cliInitCallback)();

        static::assertSame([['zettle reset onboarding', $this->command]], $this->registeredCommands);
        static::assertSame([self::COMMAND_SERVICE], $this->resolvedServices);
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
