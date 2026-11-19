<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Syde\PayPal\PointOfSale\FeatureFlags\Settings;

class SettingsTest extends TestCase
{
    private const ENV = [
        'PAYPAL_POS_FEATURE_FLAGS_URL',
        'PAYPAL_POS_FEATURE_FLAGS_TOKEN',
        'PAYPAL_POS_FEATURE_FLAGS_QA_SITE',
    ];

    protected function tearDown(): void
    {
        foreach (self::ENV as $name) {
            putenv($name);
        }
        parent::tearDown();
    }

    /**
     * Merchant sites connect to the production environment of the POS project.
     */
    public function testSitesWithoutOverridesUseTheBuiltInConnection(): void
    {
        $settings = Settings::fromEnvironment();

        self::assertSame(Settings::DEFAULT_URL, $settings->url);
        self::assertStringStartsWith('paypal-pos:production.', $settings->token);
        self::assertFalse($settings->overridden);
        self::assertFalse($settings->qaSite);
    }

    public function testEnvironmentVariablesConfigureTheConnection(): void
    {
        putenv('PAYPAL_POS_FEATURE_FLAGS_URL=https://unleash.example.com/api');
        putenv('PAYPAL_POS_FEATURE_FLAGS_TOKEN=development-token');
        $settings = Settings::fromEnvironment();

        self::assertSame('https://unleash.example.com/api', $settings->url);
        self::assertSame('development-token', $settings->token);
        self::assertTrue($settings->overridden);
    }

    /**
     * The built-in token never goes to an overridden URL.
     */
    public function testTheUrlAndTokenAreOverriddenTogether(): void
    {
        putenv('PAYPAL_POS_FEATURE_FLAGS_URL=https://unleash.example.com/api');
        $settings = Settings::fromEnvironment();

        self::assertSame('https://unleash.example.com/api', $settings->url);
        self::assertSame('', $settings->token);
        self::assertTrue($settings->overridden);
    }

    /**
     * An empty variable exported by a host doesn't turn the flags off.
     */
    public function testEmptyValuesCountAsNotSet(): void
    {
        putenv('PAYPAL_POS_FEATURE_FLAGS_URL=');
        putenv('PAYPAL_POS_FEATURE_FLAGS_TOKEN=');
        $settings = Settings::fromEnvironment();

        self::assertSame(Settings::DEFAULT_URL, $settings->url);
        self::assertFalse($settings->overridden);
    }

    /**
     * @dataProvider qaSiteValues
     */
    public function testAnEnvironmentVariableMarksQaSites(string $value, bool $expected): void
    {
        putenv("PAYPAL_POS_FEATURE_FLAGS_QA_SITE={$value}");

        self::assertSame($expected, Settings::fromEnvironment()->qaSite);
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
}
