<?php

declare(strict_types=1);

namespace Inpsyde\Queue\UnitTests;

use Brain\Monkey\Actions;
use Inpsyde\Queue\Cli\QueueCommand;
use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\QueueModule;
use Inpsyde\Queue\Queue\Runner\Runner;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Container\ContainerInterface;

/**
 * Runs in a separate process because it aliases the WP_CLI class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class QueueModuleTest extends BrainMonkeyWpTestCase
{
    private const NAMESPACE = 'acme';

    /** @var list<string> */
    private array $resolvedServices = [];

    /** @var list<array{0: string, 1: object}> */
    private array $registeredCommands = [];

    private ?\Closure $cliInitCallback = null;

    private QueueProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = Mockery::mock(QueueProcessor::class);

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
     * WHEN the queue module runs
     * THEN the queue processor is not resolved and no command is registered yet
     * AND the command registration is deferred to the cli_init action
     */
    public function testRunDefersQueueCommandRegistrationUntilCliInit(): void
    {
        $result = (new QueueModule())->run($this->container());

        static::assertTrue($result);
        static::assertNotContains('inpsyde.queue.processor', $this->resolvedServices);
        static::assertSame([], $this->registeredCommands);
        static::assertIsCallable($this->cliInitCallback);
    }

    /**
     * GIVEN the module deferred the command registration to cli_init
     * WHEN WP-CLI fires the cli_init action
     * THEN a queue command is registered under the configured namespace
     */
    public function testCliInitRegistersNamespacedQueueCommandWithResolvedProcessor(): void
    {
        (new QueueModule())->run($this->container());
        static::assertIsCallable($this->cliInitCallback);

        ($this->cliInitCallback)();

        static::assertCount(1, $this->registeredCommands);
        [$name, $command] = $this->registeredCommands[0];
        static::assertSame(self::NAMESPACE . ' queue', $name);
        static::assertInstanceOf(QueueCommand::class, $command);
    }

    private function container(): ContainerInterface
    {
        $resolved = &$this->resolvedServices;
        $processor = $this->processor;
        $runner = Mockery::mock(Runner::class)->shouldIgnoreMissing();

        $container = Mockery::mock(ContainerInterface::class);
        $container->allows('get')->andReturnUsing(
            static function (string $id) use (&$resolved, $processor, $runner) {
                $resolved[] = $id;

                switch ($id) {
                    case 'inpsyde.queue.namespace':
                        return self::NAMESPACE;
                    case 'inpsyde.queue.processor':
                        return $processor;
                    case 'inpsyde.queue.runner':
                        return $runner;
                    default:
                        return static function (): void {
                        };
                }
            }
        );

        return $container;
    }
}
