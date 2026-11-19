<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProject;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\FeatureFlags\FeatureFlagsModule;
use Syde\PayPal\PointOfSale\FeatureFlags\Flags;
use Syde\PayPal\PointOfSale\FeatureFlags\Settings;

class FeatureFlagsModuleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var array<string, mixed>
     */
    private array $transients = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        defined('DAY_IN_SECONDS') || define('DAY_IN_SECONDS', 86400);
        Functions\when('get_transient')->alias(
            fn (string $key): mixed => $this->transients[$key] ?? false
        );
        Functions\when('set_transient')->alias(
            function (string $key, mixed $value): bool {
                $this->transients[$key] = $value;

                return true;
            }
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testOtherModulesGetTheBootedFlags(): void
    {
        $flags = $this->flags('', '');
        $services = (new FeatureFlagsModule($flags))->services();

        self::assertSame($flags, $services['paypal-pos.feature-flags']($this->container()));
    }

    /**
     * A configuration error persists until it is fixed, so it is logged once a day, not on
     * every request.
     */
    public function testConfigurationErrorsAreLoggedOncePerDay(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->once()->with(
            'Feature flags are not available: {message}',
            ['message' => 'The URL and the token must be set together.']
        );
        $flags = $this->flags('https://unleash.example.com/api', '');

        // Two requests.
        (new FeatureFlagsModule($flags))->run($this->container($logger));
        (new FeatureFlagsModule($flags))->run($this->container($logger));
    }

    public function testNothingIsLoggedWithoutError(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->never();

        (new FeatureFlagsModule($this->flags('', '')))->run($this->container($logger));
    }

    private function flags(string $url, string $token): Flags
    {
        return Flags::connect(UnleashProject::builder('paypal-pos'), new Settings($url, $token), 'site-id');
    }

    private function container(?LoggerInterface $logger = null): ContainerInterface
    {
        $container = Mockery::mock(ContainerInterface::class);
        $container->allows('get')->with('paypal-pos.logger')->andReturn($logger);

        return $container;
    }
}
