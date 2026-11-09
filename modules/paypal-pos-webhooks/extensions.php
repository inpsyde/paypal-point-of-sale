<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks;

use Exception;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRecord;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Backfill\PurchaseBackfill;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job\BackfillJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job\CreateOrderJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
return [
    // These jobs call the PayPal POS API, so immediate retries don't help against rate limits or
    // short outages. With the queue's 3 retries this backs off 2, 8 and 32 minutes.
    'inpsyde.queue.failed.retry.delay' => static function (callable $previous): callable {
        return static function (JobRecord $record) use ($previous): int {
            $backedOff = [CreateOrderJob::TYPE, BackfillJob::TYPE, WebhookRegistrationJob::TYPE];
            return in_array($record->job()->type(), $backedOff, \true) ? 120 * 4 ** $record->context()->retryCount() : (int) $previous($record);
        };
    },
    'inpsyde.wc-status-report.items' => static function (array $items, ContainerInterface $container): array {
        try {
            $items[] = $container->get('inpsyde.wc-status-report.item-factory')->createReportItem(__('Pending POS order jobs', 'paypal-point-of-sale'), 'Pending POS order jobs', $container->get('inpsyde.queue.repository')->count([CreateOrderJob::TYPE]));
        } catch (Exception $exc) {
            $container->get('paypal-pos.webhook.logger')->warning(sprintf('Failed to load pending POS order jobs: %1$s', $exc->getMessage()));
        }
        return $items;
    },
    // Onboarding again re-imports the history; already synced sales and refunds are skipped.
    'paypal-pos.onboarding.resettable.options' => static function (array $previous): array {
        return array_merge($previous, [PurchaseBackfill::STATUS_OPTION, PurchaseBackfill::WEBHOOK_GAP_OPTION]);
    },
    'paypal-pos.settings.fields.registry' => static function (array $previous, ContainerInterface $container): array {
        return array_merge($previous, ['order_sync_title' => ['zettle_visible_after_onboarding' => \true, 'title' => __('POS order sync', 'paypal-point-of-sale'), 'type' => 'title', 'description' => __('Controls how PayPal POS sales appear as WooCommerce orders', 'paypal-point-of-sale')], 'order_sync_enabled' => ['zettle_visible_after_onboarding' => \true, 'title' => __('Create orders from POS sales', 'paypal-point-of-sale'), 'type' => 'select', 'default' => OrderSyncMode::ENABLED, 'description' => __('Whether PayPal POS sales are synced to WooCommerce as orders', 'paypal-point-of-sale'), 'options' => [OrderSyncMode::ENABLED => __('Create orders', 'paypal-point-of-sale'), OrderSyncMode::DISABLED => __('Don\'t create orders', 'paypal-point-of-sale')]], 'order_sync_status' => [
            'zettle_visible_after_onboarding' => \true,
            'title' => __('POS order status', 'paypal-point-of-sale'),
            'type' => 'select',
            'default' => 'wc-completed',
            'description' => __('The status assigned to orders created from POS sales', 'paypal-point-of-sale'),
            // A paid POS sale is never pending, failed, cancelled or refunded.
            'options' => array_diff_key(wc_get_order_statuses(), array_flip(['wc-pending', 'wc-failed', 'wc-cancelled', 'wc-refunded', 'wc-checkout-draft'])),
        ], 'order_sync_payment_types' => ['zettle_visible_after_onboarding' => \true, 'title' => __('Sync these payment types', 'paypal-point-of-sale'), 'type' => 'multiselect', 'class' => 'wc-enhanced-select', 'default' => [], 'description' => __('Only POS sales paid mainly with these types become orders, and only their refunds are synced. Leave empty to sync all.', 'paypal-point-of-sale'), 'options' => $container->get('paypal-pos.webhook.payment-labels')->types()], 'order_sync_auto_recovery' => ['zettle_visible_after_onboarding' => \true, 'title' => __('Recover sales after reactivation', 'paypal-point-of-sale'), 'type' => 'select', 'default' => 'yes', 'description' => __('Import the POS sales made while the plugin was deactivated when it is activated again', 'paypal-point-of-sale'), 'options' => ['yes' => __('Yes', 'paypal-point-of-sale'), 'no' => __('No', 'paypal-point-of-sale')]], 'order_sync_import_on_setup' => ['zettle_visible_after_onboarding' => \true, 'title' => __('Import past POS sales on setup', 'paypal-point-of-sale'), 'type' => 'select', 'default' => 'yes', 'description' => __('Import all past POS sales as orders once, when the setup is completed', 'paypal-point-of-sale'), 'options' => ['yes' => __('Yes', 'paypal-point-of-sale'), 'no' => __('No', 'paypal-point-of-sale')]]]);
    },
];
