<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport\Tests\Unit;

use Inpsyde\WcStatusReport\ReportItemFactory;
use Inpsyde\WcStatusReport\StatusReport;
use Inpsyde\WcStatusReport\StatusReportRenderer;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\when;

class StatusReportRendererTest extends BrainMonkeyWpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        when('esc_attr')->returnArg();
        when('esc_html')->returnArg();
    }

    public function testRender()
    {
        $itemFactory = new ReportItemFactory();
        $data = [
            $itemFactory->createReportItem('guten morgen', 'good morning', 'some value'),
            $itemFactory->createReportItem('item2', 'item2', 12342),
        ];

        $renderer = new StatusReportRenderer();

        $html = $renderer->render(new StatusReport('My Plugin', $data));

        self::assertStringContainsString('My Plugin', $html);

        self::assertStringContainsString('guten morgen', $html);
        self::assertStringContainsString('data-export-label="good morning"', $html);
        self::assertStringContainsString('some value', $html);

        self::assertStringContainsString('item2', $html);
        self::assertStringContainsString('data-export-label="item2', $html);
        self::assertStringContainsString('12342', $html);
    }
}
