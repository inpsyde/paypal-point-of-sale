<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\OrderSync\Order;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

/**
 * Shows a POS order's payment method title, not its id, on the order edit screen. Only
 * registered there, so POS methods never appear in the checkout or payment settings.
 */
class OrderScreenPaymentMethod
{
    /**
     * Runs on `current_screen`: WC loads its gateways before the screen is known.
     */
    public static function register(): void
    {
        $order = self::editedOrder();
        if ($order === null || !function_exists('WC')) {
            return;
        }

        $gateways = WC()->payment_gateways();
        if (!self::lacksRegisteredGateway($order, $gateways->payment_gateways())) {
            return;
        }

        $gateways->payment_gateways[] = new PosPaymentMethod(
            $order->get_payment_method(),
            $order->get_payment_method_title()
        );
    }

    /**
     * Whether a POS order's payment method has no loaded gateway, so WC would show its raw id
     * (e.g. paypal_pos_card) instead of the title. Such orders get a stand-in gateway.
     *
     * @param array<string, mixed> $gateways Loaded gateways by id.
     */
    public static function lacksRegisteredGateway(WC_Order $order, array $gateways): bool
    {
        $id = $order->get_payment_method();

        return $order->get_created_via() === PurchaseOrderFactory::CREATED_VIA
            && $id !== ''
            && !isset($gateways[$id]);
    }

    private static function editedOrder(): ?WC_Order
    {
        if (!OrderUtil::is_order_edit_screen()) {
            return null;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only screen detection
        // HPOS edit screens pass the order as `id`, the legacy post screen as `post`.
        $id = absint(wp_unslash($_GET['id'] ?? $_GET['post'] ?? 0));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $order = $id > 0 ? wc_get_order($id) : null;

        return $order instanceof WC_Order ? $order : null;
    }
}
