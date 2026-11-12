<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\MinorUnits;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;

/**
 * Turns a Zettle refund's lines into `wc_create_refund()` line items, matching lines of the
 * same variant (or name, for custom amounts) by still-refundable value: product lines first,
 * then fee lines. Value rather than quantity, as WC rounds quantities to whole numbers.
 */
final class RefundLines
{
    /**
     * @param array<int, mixed> $products
     *
     * @return array<int, array{qty: float, refund_total: string, refund_tax?: array<int, string>}>
     */
    public static function forRefund(WC_Order $order, array $products, string $currency): array
    {
        [$remaining, $productItems] = self::remainingValues($order, $currency);

        $refunded = [];
        foreach ($products as $line) {
            $line = (array) $line;
            $amounts = LineAmounts::fromLine($line);
            $key = self::key((string) ($line[PurchaseLine::VARIANT_UUID] ?? ''), (string) ($line[PurchaseLine::NAME] ?? ''));
            $netLeft = $amounts->netMinor();
            $taxLeft = $amounts->taxMinor();
            $quantityLeft = $amounts->quantity();

            foreach ($remaining[$key] ?? [] as $itemId => $value) {
                $net = min($netLeft, $value);
                if ($net <= 0) {
                    continue;
                }

                $share = $net / $netLeft;
                $tax = (int) round($taxLeft * $share);
                $quantity = isset($productItems[$itemId]) ? $quantityLeft * $share : 0.0;
                self::add($refunded, $itemId, $quantity, $net, $tax);
                $remaining[$key][$itemId] -= $net;
                $netLeft -= $net;
                $taxLeft -= $tax;
                $quantityLeft -= $quantity;
            }
        }

        $lineItems = [];
        foreach ($refunded as $itemId => $totals) {
            $lineItems[$itemId] = self::lineItem($order, $itemId, $totals, $currency);
        }

        return $lineItems;
    }

    /**
     * @return array{0: array<string, array<int, int>>, 1: array<int, true>} Refundable net value per
     *     item in minor units by line key (product lines first), and the product line ids.
     */
    private static function remainingValues(WC_Order $order, string $currency): array
    {
        $remaining = [];
        $productItems = [];
        foreach (['line_item', 'fee'] as $type) {
            foreach ($order->get_items($type) as $itemId => $item) {
                if (!$item instanceof WC_Order_Item_Product && !$item instanceof WC_Order_Item_Fee) {
                    continue;
                }
                $key = self::key((string) $item->get_meta(OrderMeta::ITEM_VARIANT_UUID), $item->get_name());
                $remaining[$key][(int) $itemId] = MinorUnits::toMinor(
                    (float) $item->get_total() - (float) $order->get_total_refunded_for_item($itemId, $type),
                    $currency
                );
                if ($item instanceof WC_Order_Item_Product) {
                    $productItems[(int) $itemId] = true;
                }
            }
        }

        return [$remaining, $productItems];
    }

    /**
     * @param array<int, array{qty: float, net: int, tax: int}> $refunded
     */
    private static function add(array &$refunded, int $itemId, float $quantity, int $net, int $tax): void
    {
        $refunded[$itemId] = [
            'qty' => ($refunded[$itemId]['qty'] ?? 0.0) + $quantity,
            'net' => ($refunded[$itemId]['net'] ?? 0) + $net,
            'tax' => ($refunded[$itemId]['tax'] ?? 0) + $tax,
        ];
    }

    /**
     * @param array{qty: float, net: int, tax: int} $totals
     *
     * @return array{qty: float, refund_total: string, refund_tax?: array<int, string>}
     */
    private static function lineItem(WC_Order $order, int $itemId, array $totals, string $currency): array
    {
        $lineItem = [
            'qty' => $totals['qty'],
            'refund_total' => (string) MinorUnits::toMajor($totals['net'], $currency),
        ];

        $item = $order->get_item($itemId);
        $rateIds = $item instanceof WC_Order_Item_Product || $item instanceof WC_Order_Item_Fee
            ? array_keys((array) ($item->get_taxes()['total'] ?? []))
            : [];
        if ($totals['tax'] > 0) {
            // Lines whose rate had no WC match carry no taxes; refund their tax under rate 0.
            $rateId = $rateIds !== [] ? (int) $rateIds[0] : 0;
            $lineItem['refund_tax'] = [$rateId => (string) MinorUnits::toMajor($totals['tax'], $currency)];
        }

        return $lineItem;
    }

    private static function key(string $variantUuid, string $name): string
    {
        // Sales name unnamed lines the same way, so refunds of them still match.
        return $variantUuid !== '' ? $variantUuid : 'name:' . ($name !== '' ? $name : PurchaseOrderFactory::defaultLineName());
    }
}
