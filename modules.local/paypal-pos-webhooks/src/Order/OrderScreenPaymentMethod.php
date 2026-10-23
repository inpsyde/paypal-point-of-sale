<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

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
        if (!self::needsStandIn($order, $gateways->payment_gateways())) {
            return;
        }

        $gateways->payment_gateways[] = new PosPaymentMethod(
            $order->get_payment_method(),
            $order->get_payment_method_title()
        );
    }

    /**
     * @param array<string, mixed> $gateways Loaded gateways by id.
     */
    public static function needsStandIn(WC_Order $order, array $gateways): bool
    {
        $id = $order->get_payment_method();

        return $order->get_created_via() === PurchaseOrderFactory::CREATED_VIA
            && $id !== ''
            && !isset($gateways[$id]);
    }

    private static function editedOrder(): ?WC_Order
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only screen detection
        $page = sanitize_key(wp_unslash($_GET['page'] ?? ''));
        $action = sanitize_key(wp_unslash($_GET['action'] ?? ''));
        $isHposEditScreen = $page === 'wc-orders' && $action === 'edit';
        $isPostEditScreen = ($GLOBALS['pagenow'] ?? '') === 'post.php';
        $id = absint(wp_unslash($isHposEditScreen ? ($_GET['id'] ?? 0) : ($_GET['post'] ?? 0)));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (!is_admin() || (!$isHposEditScreen && !$isPostEditScreen) || $id <= 0) {
            return null;
        }

        $order = wc_get_order($id);

        return $order instanceof WC_Order ? $order : null;
    }
}
