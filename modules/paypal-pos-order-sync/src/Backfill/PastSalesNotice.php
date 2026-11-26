<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Backfill;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Container\WritableContainerInterface;
/**
 * Points merchants who onboarded with the order-sync flag off to the historical import. Shown
 * until dismissed or the import starts.
 */
class PastSalesNotice
{
    // Set by PosOrdersModule, which loads even with the order-sync flag off.
    public const PENDING_KEY = 'order_sync_past_sales_notice';
    public const DISMISS_ACTION = 'paypal_pos_dismiss_past_sales_notice';
    private WritableContainerInterface $setupInfo;
    public function __construct(WritableContainerInterface $setupInfo)
    {
        $this->setupInfo = $setupInfo;
    }
    public function isPending(): bool
    {
        return $this->setupInfo->has(self::PENDING_KEY);
    }
    public function dismiss(): void
    {
        if ($this->isPending()) {
            $this->setupInfo->unset(self::PENDING_KEY);
        }
    }
    public function render(): void
    {
        if (!$this->isPending() || !current_user_can('manage_woocommerce') || !$this->isShownOnCurrentScreen()) {
            return;
        }
        $toolsUrl = admin_url('admin.php?page=wc-status&tab=tools');
        $dismissUrl = wp_nonce_url(admin_url('admin-post.php?action=' . self::DISMISS_ACTION), self::DISMISS_ACTION);
        ?>
        <div class="notice notice-info">
            <p>
                <?php 
        esc_html_e('PayPal Point of Sale now creates WooCommerce orders from your new POS sales. To import your past sales too, use "Import historical orders" in WooCommerce > Status > Tools.', 'paypal-point-of-sale');
        ?>
            </p>
            <p>
                <a class="button button-primary" href="<?php 
        echo esc_url($toolsUrl);
        ?>">
                    <?php 
        esc_html_e('Go to Tools', 'paypal-point-of-sale');
        ?>
                </a>
                <a class="button button-secondary" href="<?php 
        echo esc_url($dismissUrl);
        ?>">
                    <?php 
        esc_html_e('Dismiss', 'paypal-point-of-sale');
        ?>
                </a>
            </p>
        </div>
        <?php 
    }
    // Where WooCommerce shows its own notices: its screens, the dashboard and Plugins.
    private function isShownOnCurrentScreen(): bool
    {
        $screen = get_current_screen();
        if (!$screen || !function_exists('wc_get_screen_ids')) {
            return \false;
        }
        return in_array($screen->id, [...wc_get_screen_ids(), 'dashboard', 'plugins'], \true);
    }
    /**
     * Redirects without exiting; the caller must exit.
     */
    public function handleDismissRequest(): void
    {
        check_admin_referer(self::DISMISS_ACTION);
        if (current_user_can('manage_woocommerce')) {
            $this->dismiss();
        }
        // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
        wp_safe_redirect(wp_get_referer() ?: admin_url());
    }
}
