<?php

declare(strict_types=1);

use MonkeryTestCase\BrainMonkeyWpTestCase;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PastSalesNotice;
use Syde\PayPal\PointOfSale\OrderSync\Test\SetupInfoFake;
use function Brain\Monkey\Functions\stubTranslationFunctions;
use function Brain\Monkey\Functions\when;

class PastSalesNoticeTest extends BrainMonkeyWpTestCase
{
    private SetupInfoFake $setupInfo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupInfo = new SetupInfoFake();
        stubTranslationFunctions();
        when('esc_url')->returnArg();
        when('admin_url')->alias(static fn (string $path = ''): string => 'https://example.test/wp-admin/' . $path);
        when('add_query_arg')->alias(
            static fn (string $key, string $value): string => 'https://example.test/wp-admin/index.php?' . $key . '=' . $value
        );
        when('wp_nonce_url')->alias(
            static fn (string $url, string $action, string $name): string => $url . '&' . $name . '=nonce-for-' . $action
        );
        when('wc_get_screen_ids')->justReturn(['woocommerce_page_wc-status', 'product']);
    }

    private function notice(bool $pending = true, bool $orderCreationEnabled = true): PastSalesNotice
    {
        if ($pending) {
            $this->setupInfo->values[PastSalesNotice::PENDING_KEY] = 1700000000;
        }

        return new PastSalesNotice($this->setupInfo->container(), $orderCreationEnabled);
    }

    private function rendered(PastSalesNotice $notice): string
    {
        ob_start();
        $notice->render();

        return (string) ob_get_clean();
    }

    private function currentScreenIs(?string $screenId): void
    {
        when('get_current_screen')->justReturn($screenId === null ? null : (object) ['id' => $screenId]);
    }

    /**
     * GIVEN the setup info with or without the pending key
     * WHEN the notice is asked whether it is pending
     * THEN it is pending only while the key is stored
     *
     * @dataProvider pendingStates
     */
    public function testIsPendingReflectsThePendingKey(bool $stored, bool $expected): void
    {
        self::assertSame($expected, $this->notice($stored)->isPending());
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public function pendingStates(): array
    {
        return [
            'key stored' => [true, true],
            'key missing' => [false, false],
        ];
    }

    /**
     * GIVEN a pending notice
     * WHEN it is dismissed
     * THEN it is no longer pending
     */
    public function testDismissRemovesThePendingKey(): void
    {
        $notice = $this->notice();

        $notice->dismiss();

        self::assertFalse($notice->isPending());
    }

    /**
     * GIVEN a notice that is not pending
     * WHEN it is dismissed
     * THEN the setup info isn't written
     */
    public function testDismissWritesNothingWhenNotPending(): void
    {
        $this->notice(false)->dismiss();

        self::assertSame(0, $this->setupInfo->unsetCalls);
    }

    /**
     * GIVEN a pending notice and a user who manages WooCommerce
     * WHEN the notice is rendered
     * THEN it links to the WooCommerce Tools page
     * AND Dismiss links to WooCommerce's hide-notice handler with its nonce
     */
    public function testRenderShowsLinksToToolsAndWooCommerceDismiss(): void
    {
        when('current_user_can')->justReturn(true);
        $this->currentScreenIs('dashboard');

        $output = $this->rendered($this->notice());

        self::assertStringContainsString(
            'href="https://example.test/wp-admin/admin.php?page=wc-status&tab=tools"',
            $output
        );
        self::assertStringContainsString(
            'href="https://example.test/wp-admin/index.php?wc-hide-notice=' . PastSalesNotice::WOOCOMMERCE_NOTICE_NAME
            . '&_wc_notice_nonce=nonce-for-woocommerce_hide_notices_nonce"',
            $output
        );
    }

    /**
     * GIVEN a pending notice and a user who manages WooCommerce
     * WHEN the notice is rendered on a screen that shows WooCommerce notices
     * THEN the notice is output
     *
     * @dataProvider noticeScreens
     */
    public function testRenderOutputsNoticeOnWooCommerceNoticeScreens(string $screenId): void
    {
        when('current_user_can')->justReturn(true);
        $this->currentScreenIs($screenId);

        self::assertStringContainsString('notice-info', $this->rendered($this->notice()));
    }

    /**
     * @return array<string, array{string}>
     */
    public function noticeScreens(): array
    {
        return [
            'WooCommerce screen' => ['woocommerce_page_wc-status'],
            'dashboard' => ['dashboard'],
            'plugins' => ['plugins'],
        ];
    }

    /**
     * GIVEN one of the render conditions is not met
     * WHEN the notice is rendered
     * THEN nothing is output
     * AND a pending notice stays pending
     *
     * @dataProvider silentRenderStates
     */
    public function testRenderOutputsNothing(
        bool $pending,
        bool $orderCreationEnabled,
        bool $userCanManage,
        ?string $screenId
    ): void {
        when('current_user_can')->alias(
            static fn (string $capability): bool => $capability === 'manage_woocommerce' && $userCanManage
        );
        $this->currentScreenIs($screenId);
        $notice = $this->notice($pending, $orderCreationEnabled);

        self::assertSame('', $this->rendered($notice));
        self::assertSame($pending, $notice->isPending());
    }

    /**
     * @return array<string, array{bool, bool, bool, ?string}>
     */
    public function silentRenderStates(): array
    {
        return [
            'not pending' => [false, true, true, 'dashboard'],
            'order creation disabled' => [true, false, true, 'dashboard'],
            'user cannot manage WooCommerce' => [true, true, false, 'dashboard'],
            'unrelated screen' => [true, true, true, 'edit-post'],
            'no current screen' => [true, true, true, null],
        ];
    }
}
