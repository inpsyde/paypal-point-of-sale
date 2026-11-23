<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\UnitTests;

use Brain\Monkey\Actions;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Syde\PayPal\PointOfSale\Webhooks\Bootstrap;
use Syde\PayPal\PointOfSale\Webhooks\WebhookModule;

/**
 * Runs in a separate process because it aliases the WP_CLI class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class WebhookModuleTest extends BrainMonkeyWpTestCase
{
    private const COMMAND_SERVICE = 'paypal-pos.webhook.cli';

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
     * GIVEN the plugin loads its modules during plugins_loaded
     * WHEN the webhook module runs
     * THEN the CLI command service is not resolved and no command is registered yet
     * AND the registration is deferred to the cli_init action
     */
    public function testRunDefersCommandRegistrationUntilCliInit(): void
    {
        $result = (new WebhookModule())->run($this->container());

        static::assertTrue($result);
        static::assertNotContains(self::COMMAND_SERVICE, $this->resolvedServices);
        static::assertSame([], $this->registeredCommands);
        static::assertIsCallable($this->cliInitCallback);
    }

    /**
     * GIVEN the module deferred the command registration to cli_init
     * WHEN WP-CLI fires the cli_init action
     * THEN the "zettle webhook" command is registered with the resolved command service
     */
    public function testCliInitRegistersZettleWebhookCommandWithResolvedService(): void
    {
        (new WebhookModule())->run($this->container());
        static::assertIsCallable($this->cliInitCallback);

        ($this->cliInitCallback)();

        static::assertSame([['zettle webhook', $this->command]], $this->registeredCommands);
    }

    private function container(): ContainerInterface
    {
        $resolved = &$this->resolvedServices;
        $command = $this->command;
        $bootstrap = Mockery::mock(Bootstrap::class);

        $container = Mockery::mock(ContainerInterface::class);
        $container->allows('get')->andReturnUsing(
            static function (string $id) use (&$resolved, $command, $bootstrap): object {
                $resolved[] = $id;

                switch ($id) {
                    case self::COMMAND_SERVICE:
                        return $command;
                    case 'paypal-pos.webhook.bootstrap':
                        return $bootstrap;
                    default:
                        return new stdClass();
                }
            }
        );

        return $container;
    }
}
