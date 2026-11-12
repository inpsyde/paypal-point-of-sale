<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

/**
 * Resolves a WooCommerce payment method slug and title for a PayPal POS purchase.
 *
 * Multi-payment purchases are labelled by their highest-amount tender.
 */
final class PaymentLabels
{
    private const SLUG_PREFIX = 'paypal_pos';

    /**
     * @param array<int, mixed> $payments
     */
    public function primaryType(array $payments): string
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

    /**
     * @return array<string, string> Known Zettle payment types and their titles.
     */
    public function types(): array
    {
        return [
            'IZETTLE_CARD' => __('PayPal POS Card', 'paypal-point-of-sale'),
            'IZETTLE_CARD_ONLINE' => __('PayPal POS Card Online', 'paypal-point-of-sale'),
            'IZETTLE_CASH' => __('PayPal POS Cash', 'paypal-point-of-sale'),
            'GIFTCARD' => __('Gift Card (PayPal POS)', 'paypal-point-of-sale'),
            'IZETTLE_INVOICE' => __('PayPal POS Invoice', 'paypal-point-of-sale'),
            'MOBILE_PAY' => __('MobilePay (PayPal POS)', 'paypal-point-of-sale'),
            'KLARNA' => __('Klarna (PayPal POS)', 'paypal-point-of-sale'),
            'PAYPAL' => __('PayPal (PayPal POS)', 'paypal-point-of-sale'),
            'STORE_CREDIT' => __('Store Credit (PayPal POS)', 'paypal-point-of-sale'),
            'SWISH' => __('Swish (PayPal POS)', 'paypal-point-of-sale'),
            'VIPPS' => __('Vipps (PayPal POS)', 'paypal-point-of-sale'),
        ];
    }

    public function title(string $type): string
    {
        return $this->types()[$type] ?? __('PayPal POS (Other)', 'paypal-point-of-sale');
    }

    public function slug(string $type): string
    {
        if ($type === '') {
            return self::SLUG_PREFIX;
        }

        // `IZETTLE_CASH` becomes `paypal_pos_cash`.
        return sanitize_key(self::SLUG_PREFIX . '_' . strtolower((string) preg_replace('/^IZETTLE_/', '', $type)));
    }
}
