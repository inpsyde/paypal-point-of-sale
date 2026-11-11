<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProject;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProjectBuilder;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Syde\PayPal\PointOfSale\FeatureFlags\Flags;

class FlagsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const URL = 'https://unleash.example.com/api';

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
        Functions\when('get_option')->justReturn(false);
        Functions\when('add_action')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testUnknownFlagsReturnTheDefault(): void
    {
        $flags = $this->flags('', '');

        self::assertTrue($flags->isOn('unknown', true));
        self::assertFalse($flags->isOn('unknown'));
    }

    public function testChecksGoThroughTheProjectFilter(): void
    {
        Filters\expectApplied('syde.feature-flags.paypal-pos')
            ->once()
            ->with(false, 'new-sync')
            ->andReturn(true);

        self::assertTrue($this->flags('', '')->isOn('new-sync'));
    }

    /**
     * A module that checks a flag in its run() before the feature flags module has run
     * still reaches the project.
     */
    public function testTheFirstCheckConnects(): void
    {
        $connected = false;
        Filters\expectAdded('syde.feature-flags.paypal-pos')
            ->once()
            ->whenHappen(static function () use (&$connected): void {
                $connected = true;
            });
        Filters\expectApplied('syde.feature-flags.paypal-pos')
            ->twice()
            ->andReturnUsing(static function () use (&$connected): bool {
                return $connected;
            });

        $flags = $this->flags(self::URL, 'token');

        self::assertTrue($flags->isOn('new-sync'));
        self::assertTrue($flags->isOn('new-sync'));
    }

    /**
     * Code that uses the filters directly connects on its first check, early enough for the
     * library's callbacks at priority 10. Before that, nothing is built.
     */
    public function testChecksThroughTheFiltersConnect(): void
    {
        $callbacks = [];
        Functions\when('add_filter')->alias(
            static function (string $hook, callable $callback, int $priority = 10) use (&$callbacks): bool {
                $callbacks[$hook][$priority][] = $callback;

                return true;
            }
        );
        $this->flags(self::URL, 'token')->initialize();

        self::assertCount(1, $callbacks['syde.feature-flags'][0]);
        self::assertCount(1, $callbacks['syde.feature-flags.paypal-pos'][0]);
        self::assertArrayNotHasKey(10, $callbacks['syde.feature-flags.paypal-pos']);

        // Another project's check doesn't connect.
        self::assertTrue(($callbacks['syde.feature-flags'][0][0])(true, 'flag', 'other'));
        self::assertArrayNotHasKey(10, $callbacks['syde.feature-flags.paypal-pos']);

        self::assertTrue(($callbacks['syde.feature-flags'][0][0])(true, 'flag', 'paypal-pos'));
        self::assertCount(1, $callbacks['syde.feature-flags.paypal-pos'][10]);

        self::assertFalse(($callbacks['syde.feature-flags.paypal-pos'][0][0])(false, 'flag'));
        self::assertCount(1, $callbacks['syde.feature-flags.paypal-pos'][10]);
    }

    public function testTheProjectIsRegisteredOnce(): void
    {
        Filters\expectAdded('syde.feature-flags.paypal-pos')->once();
        $flags = $this->flags(self::URL, 'token');

        $flags->isOn('flag');
        $flags->isOn('flag');
    }

    public function testNothingIsRegisteredWithoutConnection(): void
    {
        Filters\expectAdded('syde.feature-flags.paypal-pos')->never();

        $this->flags('', '')->isOn('flag');
    }

    /**
     * @dataProvider incompleteConnections
     */
    public function testAnIncompleteConnectionIsNotUsed(string $url, string $token): void
    {
        Filters\expectAdded('syde.feature-flags.paypal-pos')->never();
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->once()->with(
            'Feature flags are not available: {message}',
            ['message' => 'The URL and the token must be set together.']
        );

        $this->flags($url, $token, logger: $logger)->isOn('flag');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function incompleteConnections(): array
    {
        return [
            'URL only' => [self::URL, ''],
            'token only' => ['', 'token'],
        ];
    }

    /**
     * A configuration error persists until it is fixed, so it is logged once a day, not on
     * every request.
     */
    public function testConfigurationErrorsAreLoggedOncePerDay(): void
    {
        Filters\expectAdded('syde.feature-flags.paypal-pos')->never();
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->once()->with(
            'Feature flags are not available: {message}',
            Mockery::on(static fn (array $context): bool => str_contains(
                (string) $context['message'],
                'A targeting ID is required'
            ))
        );
        $builder = UnleashProject::builder('paypal-pos');

        // Two requests.
        $this->flags(self::URL, 'token', $builder, $logger)->isOn('flag');
        $this->flags(self::URL, 'token', $builder, $logger)->isOn('flag');
    }

    private function flags(
        string $url,
        string $token,
        ?UnleashProjectBuilder $builder = null,
        ?LoggerInterface $logger = null
    ): Flags {

        $builder ??= UnleashProject::builder('paypal-pos')
            ->withTargetingId('site-id')
            ->withHttpClient(
                Mockery::mock(ClientInterface::class),
                Mockery::mock(RequestFactoryInterface::class)
            );

        return new Flags('paypal-pos', $url, $token, $builder, $logger ?? new NullLogger());
    }
}
