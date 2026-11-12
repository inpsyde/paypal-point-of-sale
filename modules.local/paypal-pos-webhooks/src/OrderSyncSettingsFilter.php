<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

/**
 * Saves a cleared payment type selection. An empty multiselect posts nothing, and the settings
 * form keeps fields that weren't posted; a saved order sync section without payment types means
 * none are selected.
 */
class OrderSyncSettingsFilter
{
    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    public function filterSettingsValues(array $settings): array
    {
        if (isset($settings['order_sync_enabled']) && !isset($settings['order_sync_payment_types'])) {
            $settings['order_sync_payment_types'] = [];
        }

        return $settings;
    }
}
