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
 * Runs in a separate process because it defines the WP_CLI constant and aliases the WP_CLI class.
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

    /** @var list<callable> */
    private array $initCallbacks = [];

    private QueueProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('WP_CLI')) {
            define('WP_CLI', true);
        }

        $this->processor = Mockery::mock(QueueProcessor::class);

        $registeredCommands = &$this->registeredCommands;
        Mockery::mock('alias:WP_CLI')
            ->shouldReceive('add_command')
            ->andReturnUsing(static function (string $name, object $command) use (&$registeredCommands): void {
                $registeredCommands[] = [$name, $command];
            });

        $initCallbacks = &$this->initCallbacks;
        Actions\expectAdded('init')->whenHappen(
            static function (callable $callback) use (&$initCallbacks): void {
                $initCallbacks[] = $callback;
            }
        );
    }

    /**
     * GIVEN the plugin runs under WP-CLI during plugins_loaded
     * WHEN the queue module runs
     * THEN the queue processor is not resolved and no command is registered yet
     * AND the work is deferred to init callbacks
     */
    public function testRunDefersQueueCommandRegistrationUntilInitWhenRunningUnderWpCli(): void
    {
        $result = (new QueueModule())->run($this->container());

        static::assertTrue($result);
        static::assertNotContains('inpsyde.queue.processor', $this->resolvedServices);
        static::assertSame([], $this->registeredCommands);
        static::assertNotEmpty($this->initCallbacks);
    }

    /**
     * GIVEN the module ran under WP-CLI and deferred its work to init
     * WHEN WordPress fires the init action
     * THEN a queue command is registered under the configured namespace
     */
    public function testInitRegistersNamespacedQueueCommandWithResolvedProcessor(): void
    {
        (new QueueModule())->run($this->container());

        foreach ($this->initCallbacks as $callback) {
            $callback();
        }

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
