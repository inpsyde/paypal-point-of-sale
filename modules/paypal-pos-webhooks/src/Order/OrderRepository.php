<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

/**
 * Looks up WooCommerce orders created from Zettle purchases by their purchase UUID.
 */
class OrderRepository
{
    public function findIdByPurchaseUuid(string $purchaseUuid): ?int
    {
        return $this->findIdByMeta(OrderMeta::PURCHASE_UUID, $purchaseUuid);
    }
    public function findIdByRefundPurchaseUuid(string $refundPurchaseUuid): ?int
    {
        return $this->findIdByMeta(OrderMeta::REFUND_PURCHASE_UUID, $refundPurchaseUuid);
    }
    /**
     * The WC refund record of a refund purchase, as opposed to an orphan refund order.
     */
    public function findRefundIdByRefundPurchaseUuid(string $refundPurchaseUuid): ?int
    {
        $ids = $this->findIdsByMeta(OrderMeta::REFUND_PURCHASE_UUID, $refundPurchaseUuid, 1, 'shop_order_refund');
        return $ids === [] ? null : $ids[0];
    }
    /**
     * @return int[] Orphan refund orders recorded for a purchase before it was synced.
     */
    public function findOrphanIdsForPurchaseUuid(string $purchaseUuid): array
    {
        return $this->findIdsByMeta(OrderMeta::ORPHAN_REFUNDS_PURCHASE_UUID, $purchaseUuid, -1);
    }
    public function existsForPurchaseUuid(string $purchaseUuid): bool
    {
        return $this->findIdByPurchaseUuid($purchaseUuid) !== null;
    }
    private function findIdByMeta(string $key, string $value): ?int
    {
        $ids = $this->findIdsByMeta($key, $value, 1);
        return $ids === [] ? null : $ids[0];
    }
    /**
     * @return int[]
     */
    private function findIdsByMeta(string $key, string $value, int $limit, string $type = ''): array
    {
        if ($value === '') {
            return [];
        }
        $ids = (array) wc_get_orders(array_filter([
            'type' => $type,
            'limit' => $limit,
            'return' => 'ids',
            // 'any' excludes trash; an explicit list including 'trash' is required.
            'status' => array_merge(array_keys(wc_get_order_statuses()), ['trash']),
            // meta_key/meta_value, not meta_query: the legacy post order store ignores meta_query.
            'meta_key' => $key,
            'meta_value' => $value,
        ]));
        return array_map('intval', array_values($ids));
    }
}
