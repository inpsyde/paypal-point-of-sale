<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WP_Error;

/**
 * Creates anonymous WooCommerce orders from Zettle POS purchases.
 *
 * Amounts arrive in minor units and are scaled here; the order never reduces stock
 * (handled by the InventoryBalanceChanged path) and sends no emails.
 */
class PurchaseOrderFactory
{
    private const KEY_VARIANT_UUID = 'variantUuid';
    private const KEY_PRODUCT_UUID = 'productUuid';
    private const KEY_QUANTITY = 'quantity';
    private const KEY_UNIT_PRICE = 'unitPrice';
    private const KEY_NAME = 'name';

    private const SUPPRESSED_EMAILS = [
        'new_order',
        'customer_completed_order',
        'customer_processing_order',
        'customer_on_hold_order',
        'customer_invoice',
    ];

    private const PAYMENT_TITLES = [
        'IZETTLE_CARD' => 'Zettle Card',
        'IZETTLE_CARD_ONLINE' => 'Zettle Card Online',
        'IZETTLE_CASH' => 'Zettle Cash',
        'PAYPAL' => 'PayPal (Zettle POS)',
        'KLARNA' => 'Klarna (Zettle POS)',
        'SWISH' => 'Swish (Zettle POS)',
    ];
    private const PAYMENT_TITLE_FALLBACK = 'Zettle POS (Other)';

    private OneToManyMapInterface $variantIdMap;

    private LoggerInterface $logger;

    public function __construct(OneToManyMapInterface $variantIdMap, LoggerInterface $logger)
    {
        $this->variantIdMap = $variantIdMap;
        $this->logger = $logger;
    }

    public function orderExists(string $purchaseUuid): bool
    {
        $ids = wc_get_orders([
            'limit' => 1,
            'return' => 'ids',
            // 'any' excludes trash; an explicit list including 'trash' is required.
            'status' => array_merge(array_keys(wc_get_order_statuses()), ['trash']),
            'meta_query' => [
                [
                    'key' => '_zettle_purchase_uuid',
                    'value' => $purchaseUuid,
                ],
            ],
        ]);

        return !empty($ids);
    }

    public function createOrder(Purchase $purchase): int
    {
        $this->suppressEmails(true);

        try {
            $order = wc_create_order();
            if ($order instanceof WP_Error) {
                throw new RuntimeException($order->get_error_message());
            }
            $divisor = 10 ** $this->minorUnitExponent($purchase->currency());

            foreach ($purchase->products() as $line) {
                $this->addLineItem($order, (array) $line, $divisor);
            }

            $order->set_currency($purchase->currency());
            $order->set_date_created($purchase->timestamp()->getTimestamp());
            $this->applyPayment($order, $purchase->payments());
            $this->applyMeta($order, $purchase);

            // Mirror Zettle's own total; never let WC recompute and diverge.
            $order->set_total((string) ($purchase->amount() / $divisor));
            // Stock is owned by the InventoryBalanceChanged path.
            $order->set_stock_reduced(true);
            $order->set_status('completed');
            $order->save();

            $order->add_order_note(sprintf('Cashier: %s', $purchase->user()->displayName()));

            return $order->get_id();
        } finally {
            $this->suppressEmails(false);
        }
    }

    private function addLineItem(WC_Order $order, array $line, int $divisor): void
    {
        $quantity = (int) ($line[self::KEY_QUANTITY] ?? 1);
        $unitPriceMinor = (int) ($line[self::KEY_UNIT_PRICE] ?? 0);
        $lineTotal = (string) ($unitPriceMinor * $quantity / $divisor);
        $variantUuid = (string) ($line[self::KEY_VARIANT_UUID] ?? '');

        $productId = $this->matchProduct($variantUuid);
        if ($productId === null) {
            $fee = new WC_Order_Item_Fee();
            $fee->set_name((string) ($line[self::KEY_NAME] ?? 'Zettle item'));
            $fee->set_amount($lineTotal);
            $fee->set_total($lineTotal);
            $order->add_item($fee);

            return;
        }

        $item = new WC_Order_Item_Product();
        $item->set_product(wc_get_product($productId));
        $item->set_quantity($quantity);
        $item->set_subtotal($lineTotal);
        $item->set_total($lineTotal);
        $item->add_meta_data('_zettle_variant_uuid', $variantUuid);
        $item->add_meta_data('_zettle_product_uuid', (string) ($line[self::KEY_PRODUCT_UUID] ?? ''));
        $item->add_meta_data('_zettle_unit_price_minor', $unitPriceMinor);
        $order->add_item($item);
    }

    private function matchProduct(string $variantUuid): ?int
    {
        if ($variantUuid === '') {
            return null;
        }

        try {
            return $this->variantIdMap->localId($variantUuid);
        } catch (IdNotFoundException $exception) {
            $this->logger->info(
                sprintf('No WooCommerce product for Zettle variant %s; adding as a fee line.', $variantUuid)
            );

            return null;
        }
    }

    /**
     * @param array<int, mixed> $payments
     */
    private function applyPayment(WC_Order $order, array $payments): void
    {
        $primaryType = $this->primaryPaymentType($payments);
        $title = self::PAYMENT_TITLES[$primaryType] ?? self::PAYMENT_TITLE_FALLBACK;
        $slug = $primaryType !== ''
            ? sanitize_key('zettle_' . strtolower($primaryType))
            : 'zettle_pos';

        $order->set_payment_method($slug);
        $order->set_payment_method_title($title);
        $order->update_meta_data('_zettle_primary_payment_type', $primaryType);
        $order->update_meta_data('_zettle_payments', wp_json_encode(array_values($payments)));
    }

    /**
     * @param array<int, mixed> $payments
     */
    private function primaryPaymentType(array $payments): string
    {
        $type = '';
        $max = null;
        foreach ($payments as $payment) {
            $payment = (array) $payment;
            $amount = abs((float) ($payment['amount'] ?? 0));
            if ($max === null || $amount > $max) {
                $max = $amount;
                $type = (string) ($payment['type'] ?? '');
            }
        }

        return $type;
    }

    private function applyMeta(WC_Order $order, Purchase $purchase): void
    {
        $order->update_meta_data('_zettle_purchase_uuid', $purchase->uuid());
        $order->update_meta_data('_zettle_purchase_number', $purchase->purchaseNumber());
        $order->update_meta_data('_created_via', 'zettle_pos');
        if ($purchase->sourceType() !== null) {
            $order->update_meta_data('_zettle_source_type', $purchase->sourceType()->getValue());
        }
    }

    private function suppressEmails(bool $suppress): void
    {
        foreach (self::SUPPRESSED_EMAILS as $emailId) {
            $filter = "woocommerce_email_enabled_{$emailId}";
            if ($suppress) {
                add_filter($filter, '__return_false');
                continue;
            }
            remove_filter($filter, '__return_false');
        }
    }

    private function minorUnitExponent(string $currency): int
    {
        $zeroDecimal = ['JPY', 'KRW', 'ISK', 'HUF', 'CLP', 'VND'];

        return in_array(strtoupper($currency), $zeroDecimal, true) ? 0 : 2;
    }
}
