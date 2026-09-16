<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

/**
 * Resolves a WooCommerce payment method slug and title for a Zettle purchase.
 *
 * Multi-payment purchases are labelled by their highest-amount tender.
 */
final class PaymentLabels
{
    private const TITLES = [
        'IZETTLE_CARD' => 'Zettle Card',
        'IZETTLE_CARD_ONLINE' => 'Zettle Card Online',
        'IZETTLE_CASH' => 'Zettle Cash',
        'PAYPAL' => 'PayPal (Zettle POS)',
        'KLARNA' => 'Klarna (Zettle POS)',
        'SWISH' => 'Swish (Zettle POS)',
    ];
    private const TITLE_FALLBACK = 'Zettle POS (Other)';
    private const SLUG_FALLBACK = 'zettle_pos';

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

    public function title(string $type): string
    {
        return self::TITLES[$type] ?? self::TITLE_FALLBACK;
    }

    public function slug(string $type): string
    {
        return $type !== '' ? sanitize_key('zettle_' . strtolower($type)) : self::SLUG_FALLBACK;
    }
}
