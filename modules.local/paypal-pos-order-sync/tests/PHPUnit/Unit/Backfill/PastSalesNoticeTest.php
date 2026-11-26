<?php

declare(strict_types=1);

use MonkeryTestCase\BrainMonkeyWpTestCase;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PastSalesNotice;
use Syde\PayPal\PointOfSale\OrderSync\Test\SetupInfoFake;
use function Brain\Monkey\Functions\expect;
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
        when('wp_nonce_url')->alias(
            static fn (string $url, string $action): string => $url . '&_wpnonce=nonce-for-' . $action
        );
        when('wc_get_screen_ids')->justReturn(['woocommerce_page_wc-status', 'product']);
    }

    private function notice(bool $pending = true): PastSalesNotice
    {
        if ($pending) {
            $this->setupInfo->values[PastSalesNotice::PENDING_KEY] = 1700000000;
        }

        return new PastSalesNotice($this->setupInfo->container());
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
     * THEN nothing is removed from the setup info, so no option write happens
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
     * AND it has a nonced dismiss link
     */
    public function testRenderShowsLinksToToolsAndNoncedDismiss(): void
    {
        when('current_user_can')->justReturn(true);
        $this->currentScreenIs('dashboard');

        $output = $this->rendered($this->notice());

        self::assertStringContainsString(
            'href="https://example.test/wp-admin/admin.php?page=wc-status&tab=tools"',
            $output
        );
        self::assertStringContainsString(
            'href="https://example.test/wp-admin/admin-post.php?action=' . PastSalesNotice::DISMISS_ACTION
            . '&_wpnonce=nonce-for-' . PastSalesNotice::DISMISS_ACTION . '"',
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
     * GIVEN a notice, a user and a screen
     * WHEN the notice is rendered
     * THEN nothing is output unless it is pending, the user manages WooCommerce and the screen shows notices
     *
     * @dataProvider silentRenderStates
     */
    public function testRenderOutputsNothing(bool $pending, bool $userCanManage, ?string $screenId): void
    {
        when('current_user_can')->alias(
            static fn (string $capability): bool => $capability === 'manage_woocommerce' && $userCanManage
        );
        $this->currentScreenIs($screenId);

        self::assertSame('', $this->rendered($this->notice($pending)));
    }

    /**
     * @return array<string, array{bool, bool, ?string}>
     */
    public function silentRenderStates(): array
    {
        return [
            'not pending' => [false, true, 'dashboard'],
            'user cannot manage WooCommerce' => [true, false, 'dashboard'],
            'unrelated screen' => [true, true, 'edit-post'],
            'no current screen' => [true, true, null],
        ];
    }

    /**
     * GIVEN a pending notice and a valid dismiss request
     * WHEN the dismiss link is handled
     * THEN the key is removed only for a user who manages WooCommerce
     * AND the user is redirected to the referer, or the admin URL without one
     *
     * @dataProvider dismissRequests
     */
    public function testHandleDismissRequest(
        string|false $referer,
        bool $userCanManage,
        string $expectedRedirect,
        bool $expectedPending
    ): void {
        $notice = $this->notice();
        when('check_admin_referer')->justReturn(1);
        when('current_user_can')->justReturn($userCanManage);
        when('wp_get_referer')->justReturn($referer);
        expect('wp_safe_redirect')->once()->with($expectedRedirect);

        $notice->handleDismissRequest();

        self::assertSame($expectedPending, $notice->isPending());
    }

    /**
     * @return array<string, array{string|false, bool, string, bool}>
     */
    public function dismissRequests(): array
    {
        $referer = 'https://example.test/wp-admin/plugins.php';

        return [
            'manager with referer' => [$referer, true, $referer, false],
            'manager without referer' => [false, true, 'https://example.test/wp-admin/', false],
            'user without capability' => [$referer, false, $referer, true],
        ];
    }

    /**
     * GIVEN a pending notice and a dismiss link with an invalid nonce
     * WHEN the dismiss link is handled
     * THEN the request stops without a redirect
     * AND the notice stays pending
     */
    public function testHandleDismissRequestKeepsTheNoticeWhenTheNonceIsInvalid(): void
    {
        $notice = $this->notice();
        when('check_admin_referer')->alias(static function (): void {
            throw new RuntimeException('Invalid nonce');
        });
        when('current_user_can')->justReturn(true);
        expect('wp_safe_redirect')->never();

        try {
            $notice->handleDismissRequest();
            self::fail('The invalid nonce must stop the request.');
        } catch (RuntimeException $exception) {
            self::assertTrue($notice->isPending());
        }
    }
}
