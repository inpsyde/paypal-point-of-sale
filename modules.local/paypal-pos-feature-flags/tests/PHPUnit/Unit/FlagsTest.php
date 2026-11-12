<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Inpsyde\FeatureFlags\Adapter\Unleash\RegistrationMode;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProject;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProjectBuilder;
use Inpsyde\FeatureFlags\FeatureFlags;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Syde\PayPal\PointOfSale\FeatureFlags\Flags;
use Syde\PayPal\PointOfSale\FeatureFlags\Settings;

class FlagsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const URL = 'https://unleash.example.com/api';

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        foreach (['HOUR_IN_SECONDS' => 3600, 'DAY_IN_SECONDS' => 86400] as $name => $value) {
            defined($name) || define($name, $value);
        }
        require_once __DIR__ . '/../Stubs/WP_Http.php';
        Functions\when('get_option')->alias(
            static fn (string $name): mixed => $name === 'home' ? 'https://shop.example.com' : false
        );
        Functions\when('wp_salt')->justReturn('secret-salt');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * The request settings keep each site within its share of the Unleash request budget.
     */
    public function testRequestSettingsStayWithinTheBudget(): void
    {
        $builder = $this->builder();

        self::assertSame(12 * 3600, $this->property($builder, 'freshTtl'));
        self::assertSame(0.2, $this->property($builder, 'ttlJitter'));
        self::assertSame(7 * 86400, $this->property($builder, 'staleTtl'));
        self::assertTrue($this->property($builder, 'metricsEnabled'));
        self::assertSame(86400, $this->property($builder, 'metricsInterval'));
        self::assertSame(RegistrationMode::Off, $this->property($builder, 'registration'));
    }

    public function testTheContextGetsThePluginVersionAndSiteId(): void
    {
        $builder = $this->builder();

        self::assertSame('3.1.0', $this->property($builder, 'packageVersion'));
        self::assertSame('site-id', $this->property($builder, 'targetingId'));
        self::assertSame([], $this->property($builder, 'properties'));
    }

    public function testDevelopmentBuildsReportADevVersion(): void
    {
        self::assertSame('0.0.0-dev', $this->property($this->builder('{VERSION}'), 'packageVersion'));
    }

    public function testQaSitesGetTheQaSiteProperty(): void
    {
        self::assertSame(
            ['qaSite' => 'true'],
            $this->property($this->builder(qaSite: true), 'properties')
        );
    }

    /**
     * A different ID would move every site into another rollout bucket and break siteId
     * lists in Unleash, so the salt scheme and the formula must not change.
     */
    public function testTheSiteIdStaysTheSame(): void
    {
        Functions\when('wp_salt')->alias(static fn (string $scheme): string => "salt-{$scheme}");

        self::assertSame(
            hash_hmac('sha256', 'shop.example.com', 'salt-feature_flags'),
            Flags::boot('3.1.0')->siteId()
        );
    }

    /**
     * Merchant sites connect at boot, before any module is loaded.
     */
    public function testBootRegistersTheProject(): void
    {
        $flags = Flags::boot('3.1.0');

        self::assertTrue(FeatureFlags::hasProject('paypal-pos'));
        self::assertFalse($flags->settings()->overridden);
        self::assertNull($flags->configurationError());
    }

    public function testChecksGoThroughTheProjectFilter(): void
    {
        Filters\expectApplied('syde.feature-flags.paypal-pos')
            ->once()
            ->with(false, 'new-sync')
            ->andReturn(true);

        self::assertTrue($this->flags('', '')->isOn('new-sync'));
    }

    public function testUnknownFlagsReturnTheDefault(): void
    {
        $flags = $this->flags('', '');

        self::assertTrue($flags->isOn('unknown', true));
        self::assertFalse($flags->isOn('unknown'));
    }

    public function testNothingIsRegisteredWithoutConnection(): void
    {
        Filters\expectAdded('syde.feature-flags.paypal-pos')->never();

        self::assertNull($this->flags('', '')->configurationError());
    }

    /**
     * @dataProvider incompleteConnections
     */
    public function testAnIncompleteConnectionIsNotUsed(string $url, string $token): void
    {
        Filters\expectAdded('syde.feature-flags.paypal-pos')->never();

        self::assertSame(
            'The URL and the token must be set together.',
            $this->flags($url, $token)->configurationError()
        );
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
     * A configuration error must not take the shop down.
     */
    public function testAnInvalidConnectionIsKeptAsError(): void
    {
        Filters\expectAdded('syde.feature-flags.paypal-pos')->never();

        $flags = Flags::connect(
            UnleashProject::builder('paypal-pos'),
            new Settings(self::URL, 'token'),
            'site-id'
        );

        self::assertStringContainsString('A targeting ID is required', (string) $flags->configurationError());
    }

    /**
     * Not only the library's validation: a broken registered-project filter, for example.
     */
    public function testAnyErrorWhileRegisteringIsKept(): void
    {
        Filters\expectApplied('syde.feature-flags.registered-project')
            ->andReturnUsing(static function (): never {
                throw new \TypeError('Broken filter');
            });

        self::assertSame('Broken filter', $this->flags(self::URL, 'token')->configurationError());
    }

    public function testModulesWithoutFlagAreAlwaysLoaded(): void
    {
        self::assertSame(['A', 'B'], $this->flags('', '')->modules(['A', 'B']));
    }

    public function testAGatedModuleIsLoadedWhileItsFlagIsOn(): void
    {
        Filters\expectApplied('syde.feature-flags.paypal-pos')
            ->andReturnUsing(static fn (bool $default, string $flag): bool => $flag === 'b-flag');

        self::assertSame(
            ['A', 'B'],
            $this->flags('', '')->modules(['A', 'B' => 'b-flag', 'C' => 'c-flag'])
        );
    }

    public function testAllModulesCanBeLoadedRegardlessOfTheirFlag(): void
    {
        Filters\expectApplied('syde.feature-flags.paypal-pos')->never();

        self::assertSame(
            ['A', 'B', 'C'],
            $this->flags('', '')->modules(['A', 'B' => 'b-flag', 'C' => 'c-flag'], true)
        );
    }

    private function builder(string $version = '3.1.0', bool $qaSite = false): UnleashProjectBuilder
    {
        return Flags::builder(
            'site-id',
            $version,
            $qaSite,
            Mockery::mock(ClientInterface::class),
            Mockery::mock(RequestFactoryInterface::class)
        );
    }

    private function flags(string $url, string $token): Flags
    {
        return Flags::connect($this->builder(), new Settings($url, $token), 'site-id');
    }

    private function property(object $object, string $name): mixed
    {
        return (new \ReflectionProperty($object, $name))->getValue($object);
    }
}
