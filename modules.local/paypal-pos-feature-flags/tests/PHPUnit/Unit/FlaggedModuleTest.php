<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProject;
use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\FactoryModule;
use Inpsyde\Modularity\Module\Module;
use Inpsyde\Modularity\Module\ServiceModule;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\FeatureFlags\FlaggedModule;
use Syde\PayPal\PointOfSale\FeatureFlags\Flags;
use Syde\PayPal\PointOfSale\FeatureFlags\Settings;

class FlaggedModuleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testAModuleIsActiveWhileItsFlagIsOn(): void
    {
        $this->flag('my-flag', true);
        $container = Mockery::mock(ContainerInterface::class);
        $inner = $this->module(true);
        $module = new FlaggedModule($this->flags(), 'my-flag', $inner);

        self::assertSame(['service' => 'service'], array_map(static fn (callable $factory) => $factory(), $module->services()));
        self::assertSame(['factory' => 'factory'], array_map(static fn (callable $factory) => $factory(), $module->factories()));
        self::assertSame(['extension'], array_keys($module->extensions()));
        self::assertTrue($module->run($container));
        self::assertSame('inner', $module->id());
    }

    public function testAModuleDoesNothingWhileItsFlagIsOff(): void
    {
        $this->flag('my-flag', false);
        $module = new FlaggedModule($this->flags(), 'my-flag', $this->module(false));

        self::assertSame([], $module->services());
        self::assertSame([], $module->factories());
        self::assertSame([], $module->extensions());
        self::assertTrue($module->run(Mockery::mock(ContainerInterface::class)));
        self::assertSame('inner', $module->id());
    }

    /**
     * Once per request, so the services and run() agree.
     */
    public function testTheFlagIsCheckedOnce(): void
    {
        Filters\expectApplied('syde.feature-flags.paypal-pos')->once()->andReturn(true);
        $module = new FlaggedModule($this->flags(), 'my-flag', $this->module(true));

        $module->services();
        $module->extensions();
        $module->run(Mockery::mock(ContainerInterface::class));
    }

    /**
     * A module that only provides services has nothing to run.
     */
    public function testAModuleWithoutRunIsDone(): void
    {
        $this->flag('my-flag', true);
        $inner = Mockery::mock(ServiceModule::class);
        $inner->allows('services')->andReturn([]);

        $module = new FlaggedModule($this->flags(), 'my-flag', $inner);

        self::assertSame([], $module->factories());
        self::assertTrue($module->run(Mockery::mock(ContainerInterface::class)));
    }

    private function flag(string $name, bool $on): void
    {
        Filters\expectApplied('syde.feature-flags.paypal-pos')
            ->with(false, $name)
            ->andReturn($on);
    }

    private function module(bool $runs): Module
    {
        $module = Mockery::mock(
            ServiceModule::class,
            FactoryModule::class,
            ExtendingModule::class,
            ExecutableModule::class
        );
        $module->allows('id')->andReturn('inner');
        $module->allows('services')->andReturn(['service' => static fn (): string => 'service']);
        $module->allows('factories')->andReturn(['factory' => static fn (): string => 'factory']);
        $module->allows('extensions')->andReturn(['extension' => static fn (mixed $value): mixed => $value]);
        if ($runs) {
            $module->expects('run')->once()->andReturn(true);
        } else {
            $module->expects('run')->never();
        }

        return $module;
    }

    private function flags(): Flags
    {
        return Flags::connect(UnleashProject::builder('paypal-pos'), new Settings('', ''), 'site-id');
    }
}
