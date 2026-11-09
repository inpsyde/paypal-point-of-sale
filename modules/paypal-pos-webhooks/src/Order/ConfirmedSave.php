<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

use RuntimeException;
use WC_Data;
/**
 * WooCommerce logs storage failures, and exceptions other plugins throw from save hooks, instead
 * of throwing them. A save only counts once it got through to its last hook and the record can be
 * found; otherwise the partly saved record is deleted, or the caller's retry would create a second
 * one next to it.
 */
final class ConfirmedSave
{
    /**
     * @param string $objectType Fires woocommerce_after_{$objectType}_object_save, e.g. `order`.
     * @param callable(): WC_Data $save Saves the record and returns it.
     * @param callable(): bool $isFound Looks the saved record up again.
     * @param string $failure Escaped message for the exception.
     *
     * @throws RuntimeException
     */
    public static function run(string $objectType, callable $save, callable $isFound, string $failure): void
    {
        $savedIds = [];
        $recordSave = static function (WC_Data $saved) use (&$savedIds): void {
            $savedIds[] = $saved->get_id();
        };
        $hook = "woocommerce_after_{$objectType}_object_save";
        // First of all listeners, so a later one throwing can't hide a completed save.
        // phpcs:ignore Syde.WordPress.HookPriority.PHP_INT_MIN -- removed again right below.
        add_action($hook, $recordSave, \PHP_INT_MIN);
        try {
            $record = $save();
        } finally {
            remove_action($hook, $recordSave, \PHP_INT_MIN);
        }
        if (in_array($record->get_id(), $savedIds, \true) && $isFound()) {
            return;
        }
        if ($record->get_id() > 0) {
            $record->delete(\true);
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- escaped by the caller.
        throw new RuntimeException($failure);
    }
}
