<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Inpsyde\FeatureFlags\Adapter\Unleash\RegistrationMode;
use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProjectBuilder;
use Inpsyde\Modularity\Package;
use Inpsyde\Modularity\Properties\Properties;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Syde\PayPal\PointOfSale\FeatureFlags\Flags;

class ServicesTest extends TestCase
{
    private const ENV = [
        'PAYPAL_POS_FEATURE_FLAGS_URL',
        'PAYPAL_POS_FEATURE_FLAGS_TOKEN',
        'PAYPAL_POS_FEATURE_FLAGS_QA_SITE',
    ];

    private string $homeUrl = 'https://shop.example.com';

    private string $version = '3.1.0';

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        foreach (['HOUR_IN_SECONDS' => 3600, 'DAY_IN_SECONDS' => 86400] as $name => $value) {
            defined($name) || define($name, $value);
        }
        Functions\when('get_option')->alias(
            fn (string $name): mixed => $name === 'home' ? $this->homeUrl : false
        );
        Functions\when('wp_salt')->justReturn('secret-salt');
    }

    protected function tearDown(): void
    {
        foreach (self::ENV as $name) {
            putenv($name);
        }
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * The request settings keep each site within its share of the Unleash request budget.
     */
    public function testRequestSettingsStayWithinTheBudget(): void
    {
        $builder = $this->container()->get('paypal-pos.feature-flags.unleash.builder');
        self::assertInstanceOf(UnleashProjectBuilder::class, $builder);

        self::assertSame(12 * 3600, $this->property($builder, 'freshTtl'));
        self::assertSame(0.2, $this->property($builder, 'ttlJitter'));
        self::assertSame(7 * 86400, $this->property($builder, 'staleTtl'));
        self::assertTrue($this->property($builder, 'metricsEnabled'));
        self::assertSame(86400, $this->property($builder, 'metricsInterval'));
        self::assertSame(RegistrationMode::Off, $this->property($builder, 'registration'));
    }

    /**
     * Not the shared client, which adds Zettle headers.
     */
    public function testTheProjectUsesThePlainClient(): void
    {
        $container = $this->container();
        $builder = $container->get('paypal-pos.feature-flags.unleash.builder');

        self::assertSame(
            $container->get('inpsyde.http-client.inner-client'),
            $this->property($builder, 'httpClient')
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
            $this->container()->get('paypal-pos.feature-flags.site-id')
        );
    }

    public function testTheContextGetsThePluginVersionAndSiteId(): void
    {
        $container = $this->container();
        $builder = $container->get('paypal-pos.feature-flags.unleash.builder');

        self::assertSame('3.1.0', $this->property($builder, 'packageVersion'));
        self::assertSame(
            $container->get('paypal-pos.feature-flags.site-id'),
            $this->property($builder, 'targetingId')
        );
        self::assertSame([], $this->property($builder, 'properties'));
    }

    public function testDevelopmentBuildsReportADevVersion(): void
    {
        $this->version = '{VERSION}';

        self::assertSame(
            '0.0.0-dev',
            $this->container()->get('paypal-pos.feature-flags.package-version')
        );
    }

    public function testFeatureFlagsAreOffWithoutConnection(): void
    {
        self::assertFalse(
            $this->container([
                'paypal-pos.feature-flags.default-url' => '',
                'paypal-pos.feature-flags.default-token' => '',
            ])->get('paypal-pos.feature-flags.enabled')
        );
        self::assertTrue(
            $this->container([
                'paypal-pos.feature-flags.url' => 'https://unleash.example.com/api',
                'paypal-pos.feature-flags.token' => 'token',
            ])->get('paypal-pos.feature-flags.enabled')
        );
    }

    /**
     * Merchant sites connect to the production environment of the POS project.
     */
    public function testSitesWithoutOverridesUseTheBuiltInConnection(): void
    {
        $container = $this->container();

        self::assertSame(
            $container->get('paypal-pos.feature-flags.default-url'),
            $container->get('paypal-pos.feature-flags.url')
        );
        self::assertStringStartsWith(
            'paypal-pos:production.',
            $container->get('paypal-pos.feature-flags.token')
        );
        self::assertTrue($container->get('paypal-pos.feature-flags.enabled'));
    }

    /**
     * The built-in token never goes to an overridden URL.
     */
    public function testTheUrlAndTokenAreOverriddenTogether(): void
    {
        putenv('PAYPAL_POS_FEATURE_FLAGS_URL=https://unleash.example.com/api');
        $container = $this->container([
            'paypal-pos.feature-flags.default-url' => 'https://built-in.example.com/api',
            'paypal-pos.feature-flags.default-token' => 'built-in-token',
        ]);

        self::assertSame('https://unleash.example.com/api', $container->get('paypal-pos.feature-flags.url'));
        self::assertSame('', $container->get('paypal-pos.feature-flags.token'));
        self::assertFalse($container->get('paypal-pos.feature-flags.enabled'));
    }

    public function testAnIncompleteOverrideIsLogged(): void
    {
        putenv('PAYPAL_POS_FEATURE_FLAGS_TOKEN=development-token');
        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->justReturn(true);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('error')->once()->with(
            'Feature flags are not available: {message}',
            ['message' => 'The URL and the token must be set together.']
        );

        $flags = $this->container(['paypal-pos.logger' => $logger])->get('paypal-pos.feature-flags');
        self::assertInstanceOf(Flags::class, $flags);
        $flags->isOn('flag');
    }

    public function testQaSitesGetTheQaSiteProperty(): void
    {
        $container = $this->container(['paypal-pos.feature-flags.qa-site' => true]);

        self::assertSame(
            ['qaSite' => 'true'],
            $container->get('paypal-pos.feature-flags.properties')
        );
    }

    public function testTheQaSitePropertyKeepsOtherProperties(): void
    {
        $extensions = require __DIR__ . '/../../../extensions.php';
        $container = $this->container(['paypal-pos.feature-flags.qa-site' => true]);

        self::assertSame(
            ['qaSite' => 'true', 'betaProgram' => 'true'],
            $extensions['paypal-pos.feature-flags.properties'](['betaProgram' => 'true'], $container)
        );
    }

    public function testEnvironmentVariablesConfigureTheConnection(): void
    {
        putenv('PAYPAL_POS_FEATURE_FLAGS_URL=https://unleash.example.com/api');
        putenv('PAYPAL_POS_FEATURE_FLAGS_TOKEN=development-token');
        $container = $this->container();

        self::assertSame('https://unleash.example.com/api', $container->get('paypal-pos.feature-flags.url'));
        self::assertSame('development-token', $container->get('paypal-pos.feature-flags.token'));
        self::assertTrue($container->get('paypal-pos.feature-flags.enabled'));
    }

    /**
     * @dataProvider qaSiteValues
     */
    public function testAnEnvironmentVariableMarksQaSites(string $value, bool $expected): void
    {
        putenv("PAYPAL_POS_FEATURE_FLAGS_QA_SITE={$value}");

        self::assertSame($expected, $this->container()->get('paypal-pos.feature-flags.qa-site'));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function qaSiteValues(): array
    {
        return [
            'true' => ['true', true],
            '1' => ['1', true],
            'false' => ['false', false],
            '0' => ['0', false],
            'empty' => ['', false],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function container(array $overrides = []): ContainerInterface
    {
        $properties = Mockery::mock(Properties::class);
        $properties->allows('version')->andReturnUsing(fn (): string => $this->version);

        $services = require __DIR__ . '/../../../services.php';
        $extensions = require __DIR__ . '/../../../extensions.php';
        foreach ($extensions as $id => $extension) {
            $factory = $services[$id];
            $services[$id] = static fn (ContainerInterface $container): mixed
                => $extension($factory($container), $container);
        }
        $values = $overrides + [
            Package::PROPERTIES => $properties,
            'paypal-pos.logger' => new NullLogger(),
            'inpsyde.http-client.inner-client' => Mockery::mock(ClientInterface::class),
            'inpsyde.http-client.request-factory' => Mockery::mock(RequestFactoryInterface::class),
        ];

        return new class ($services, $values) implements ContainerInterface {
            /**
             * @param array<string, callable> $services
             * @param array<string, mixed> $values
             */
            public function __construct(private array $services, private array $values)
            {
            }

            public function get(string $id): mixed
            {
                if (!array_key_exists($id, $this->values)) {
                    if (!isset($this->services[$id])) {
                        throw new class ("Unknown service {$id}")
                            extends \RuntimeException
                            implements NotFoundExceptionInterface {
                        };
                    }
                    $this->values[$id] = ($this->services[$id])($this);
                }

                return $this->values[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->values) || isset($this->services[$id]);
            }
        };
    }

    private function property(object $object, string $name): mixed
    {
        $property = new \ReflectionProperty($object, $name);

        return $property->getValue($object);
    }
}
