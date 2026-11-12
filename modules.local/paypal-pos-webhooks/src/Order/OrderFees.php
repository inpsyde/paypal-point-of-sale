<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\MinorUnits;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use WC_Order;
use WC_Order_Item_Fee;

/**
 * Fee lines of POS orders.
 */
final class OrderFees
{
    public static function create(string $name, int $amountMinor, string $currency): WC_Order_Item_Fee
    {
        $amount = (string) MinorUnits::toMajor($amountMinor, $currency);
        $fee = new WC_Order_Item_Fee();
        $fee->set_name($name);
        $fee->set_amount($amount);
        $fee->set_total($amount);

        return $fee;
    }

    /**
     * Adds the part of the purchase amount no sold line covers: tips, then anything left, such
     * as a service charge. Negated with $sign -1 for refunds.
     */
    public static function addUnlistedCharges(WC_Order $order, Purchase $purchase, int $gapMinor, int $sign = 1): void
    {
        if ($gapMinor <= 0) {
            return;
        }

        $tipMinor = 0;
        foreach ($purchase->payments() as $payment) {
            $tipMinor += abs((int) (((array) $payment)['gratuityAmount'] ?? 0));
        }
        $tipMinor = min($tipMinor, $gapMinor);

        $charges = [
            __('Tip', 'paypal-point-of-sale') => $tipMinor,
            __('Other charges', 'paypal-point-of-sale') => $gapMinor - $tipMinor,
        ];
        foreach ($charges as $name => $amountMinor) {
            if ($amountMinor > 0) {
                $fee = self::create((string) $name, $sign * $amountMinor, $purchase->currency());
                $fee->set_tax_status('none');
                $order->add_item($fee);
            }
        }
    }
}
