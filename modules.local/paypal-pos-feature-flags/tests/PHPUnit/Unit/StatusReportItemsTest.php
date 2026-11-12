<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionError;
use Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionSource;
use Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionStatus;
use Inpsyde\WcStatusReport\ReportItemFactory;
use Inpsyde\WcStatusReport\ReportItemInterface;
use PHPUnit\Framework\TestCase;
use Syde\PayPal\PointOfSale\FeatureFlags\StatusReportItems;

class StatusReportItemsTest extends TestCase
{
    private const FETCHED = 1794387774;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\stubTranslationFunctions();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testAHealthySite(): void
    {
        $items = $this->items(
            new ConnectionStatus(ConnectionSource::Fresh, self::FETCHED),
            ['order-sync' => false, 'new-checkout' => true]
        );

        self::assertSame(
            [
                'Feature flags site ID' => 'site-id',
                'Feature flags configuration' => 'built-in, QA site: no',
                'Feature flags source' => 'fresh',
                'Feature flags last successful fetch' => '2026-11-11 09:02:54 UTC',
                'Feature flags last error' => 'none',
                'Feature flags' => 'new-checkout: on, order-sync: off',
            ],
            $items
        );
    }

    /**
     * Exported reports are read by support, so the exported labels stay in English.
     */
    public function testExportedLabelsAreEnglish(): void
    {
        Functions\when('__')->alias(static fn (string $text): string => "translated {$text}");

        $item = (new StatusReportItems(new ReportItemFactory(), 'site-id', false, false))
            ->items(new ConnectionStatus(ConnectionSource::Fresh), [])[0];

        self::assertSame('translated Feature flags site ID', $item->getLabel());
        self::assertSame('Feature flags site ID', $item->getExportedLabel());
    }

    public function testAnUnreachableUnleash(): void
    {
        $items = $this->items(
            new ConnectionStatus(
                ConnectionSource::Stale,
                self::FETCHED,
                new ConnectionError(self::FETCHED + 3600, \RuntimeException::class, 503, 'Service Unavailable')
            ),
            ['order-sync' => false]
        );

        self::assertSame('stale copy (Unleash unreachable)', $items['Feature flags source']);
        self::assertSame('2026-11-11 10:02:54 UTC: Service Unavailable', $items['Feature flags last error']);
        self::assertSame('order-sync: off', $items['Feature flags']);
    }

    public function testASiteWithoutUsableCopy(): void
    {
        $items = $this->items(new ConnectionStatus(ConnectionSource::Defaults), []);

        self::assertSame('code defaults (no usable copy)', $items['Feature flags source']);
        self::assertSame('never', $items['Feature flags last successful fetch']);
        self::assertSame('none', $items['Feature flags']);
    }

    public function testAConfigurationErrorIsTheLastError(): void
    {
        $items = $this->items(
            new ConnectionStatus(ConnectionSource::Defaults),
            [],
            'The URL and the token must be set together.'
        );

        self::assertSame('The URL and the token must be set together.', $items['Feature flags last error']);
    }

    public function testAQaSite(): void
    {
        $items = $this->items(
            new ConnectionStatus(ConnectionSource::Fresh, self::FETCHED),
            [],
            overridden: true,
            qaSite: true
        );

        self::assertSame('overridden, QA site: yes', $items['Feature flags configuration']);
    }

    /**
     * @param array<string, bool> $flags
     * @return array<string, mixed> Exported label => value.
     */
    private function items(
        ConnectionStatus $connection,
        array $flags,
        ?string $configurationError = null,
        bool $overridden = false,
        bool $qaSite = false
    ): array {

        $items = (new StatusReportItems(new ReportItemFactory(), 'site-id', $overridden, $qaSite))
            ->items($connection, $flags, $configurationError);

        return array_combine(
            array_map(static fn (ReportItemInterface $item): string => $item->getExportedLabel(), $items),
            array_map(static fn (ReportItemInterface $item): mixed => $item->getValue(), $items)
        );
    }
}
