<?php

declare(strict_types=1);

use MonkeryTestCase\BrainMonkeyWpTestCase;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\BackfillTools;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PastSalesNotice;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\OrderSync\Test\SetupInfoFake;
use function Brain\Monkey\Functions\stubEscapeFunctions;
use function Brain\Monkey\Functions\stubTranslationFunctions;
use function Brain\Monkey\Functions\when;

class BackfillToolsTest extends BrainMonkeyWpTestCase
{
    private SetupInfoFake $setupInfo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupInfo = new SetupInfoFake([PastSalesNotice::PENDING_KEY => 1700000000]);
        stubTranslationFunctions();
        stubEscapeFunctions();
        $options = [];
        when('get_option')->alias(
            static function (string $name, $default = false) use (&$options) {
                return $options[$name] ?? $default;
            }
        );
        when('update_option')->alias(static function (string $name, $value) use (&$options): bool {
            $options[$name] = $value;

            return true;
        });
        when('delete_option')->alias(static function (string $name) use (&$options): bool {
            unset($options[$name]);

            return true;
        });
    }

    private function backfill(bool $enabled = true): PurchaseBackfill
    {
        return new PurchaseBackfill(
            static function (string $type, array $args): void {
            },
            $enabled,
            static fn (): bool => true,
            $this->setupInfo->container(),
            true,
            true
        );
    }

    /**
     * @return array<string, array{callback: callable, requires_refresh: bool}>
     */
    private function tools(PurchaseBackfill $backfill): array
    {
        return (new BackfillTools($backfill, new PastSalesNotice($this->setupInfo->container())))->addTools([]);
    }

    /**
     * GIVEN a pending past sales notice
     * WHEN the merchant runs a tool
     * THEN the notice is dismissed only when the historical import started
     *
     * @dataProvider noticeAfterToolProvider
     */
    public function testNoticeIsDismissedOnlyByASuccessfulHistoryImport(
        string $toolId,
        bool $syncEnabled,
        bool $importAlreadyRunning,
        bool $expectedPending
    ): void {
        $backfill = $this->backfill($syncEnabled);
        if ($importAlreadyRunning) {
            $backfill->start();
        }

        $this->tools($backfill)[$toolId]['callback']();

        self::assertSame($expectedPending, $this->setupInfo->has(PastSalesNotice::PENDING_KEY));
    }

    /**
     * @return array<string, array{string, bool, bool, bool}>
     */
    public function noticeAfterToolProvider(): array
    {
        return [
            'history import starts' => ['zettle_import_orders', true, false, false],
            'history import already running' => ['zettle_import_orders', true, true, true],
            'history import with order sync disabled' => ['zettle_import_orders', false, false, true],
            'missed orders sync' => ['zettle_sync_missed_orders', true, false, true],
        ];
    }

    /**
     * GIVEN the order import tools on the WooCommerce status tools page
     * WHEN the tools list is built
     * THEN each tool asks WooCommerce to refresh the page after it runs
     *
     * @dataProvider toolIdProvider
     */
    public function testToolRequestsRefreshAfterRunning(string $toolId): void
    {
        self::assertTrue($this->tools($this->backfill())[$toolId]['requires_refresh']);
    }

    /**
     * @return array<string, array{string}>
     */
    public function toolIdProvider(): array
    {
        return [
            'historical import' => ['zettle_import_orders'],
            'missed orders sync' => ['zettle_sync_missed_orders'],
        ];
    }
}
