<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Backfill;

/**
 * Asks on the last onboarding settings step whether to import past sales once setup completes.
 */
class SetupImportChoice
{
    public static function render(): void
    {
        ?>
        <p>
            <input type="hidden" name="woocommerce_zettle_order_sync_import_on_setup" value="no">
            <label>
                <input type="checkbox" name="woocommerce_zettle_order_sync_import_on_setup" value="yes" checked>
                <?php esc_html_e(
                    'Import past PayPal POS sales as WooCommerce orders when the setup is completed',
                    'paypal-point-of-sale'
                ); ?>
            </label>
        </p>
        <?php
    }
}
