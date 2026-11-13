<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync;

use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Psr\Log\NullLogger;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\OnboardingState;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Backfill\BackfillTools;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Handler\PurchaseCreatedHandler;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Job\BackfillJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Job\CreateOrderJob;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\OrderDefaults;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\OrderRepository;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\OrderTaxes;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\PaymentLabels;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseFilter;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseRefundFactory;
$job = static function (string $type): string {
    return "paypal-pos.job.{$type}";
};
return [$job(CreateOrderJob::TYPE) => static function (C $container): Job {
    return new CreateOrderJob($container->get('paypal-pos.sdk.api.purchases'), $container->get('paypal-pos.order-sync.order-factory'), $container->get('paypal-pos.order-sync.refund-factory'), $container->get('paypal-pos.order-sync.enabled'), $container->get('paypal-pos.order-sync.purchase-filter'));
}, $job(BackfillJob::TYPE) => static function (C $container): Job {
    return new BackfillJob($container->get('paypal-pos.sdk.api.purchases'), $container->get('paypal-pos.order-sync.order-factory'), $container->get('paypal-pos.order-sync.refund-factory'), $container->get('paypal-pos.order-sync.backfill'), $container->get('paypal-pos.order-sync.purchase-filter'), $container->get('inpsyde.queue.failed.retry.count'), $container->get('inpsyde.queue.enqueue-job'));
}, 'paypal-pos.order-sync.payment-labels' => static function (): PaymentLabels {
    return new PaymentLabels();
}, 'paypal-pos.order-sync.order-repository' => static function (): OrderRepository {
    return new OrderRepository();
}, 'paypal-pos.order-sync.order-defaults' => static function (C $container): OrderDefaults {
    return new OrderDefaults($container->get('paypal-pos.order-sync.payment-labels'));
}, 'paypal-pos.order-sync.order-taxes' => static function (): OrderTaxes {
    return new OrderTaxes();
}, 'paypal-pos.order-sync.order-factory' => static function (C $container): PurchaseOrderFactory {
    return new PurchaseOrderFactory($container->get('paypal-pos.sdk.id-map.variant'), $container->get('paypal-pos.order-sync.order-defaults'), $container->get('paypal-pos.order-sync.order-taxes'), $container->get('paypal-pos.order-sync.order-repository'), $container->get('paypal-pos.order-sync.logger'), $container->get('paypal-pos.order-sync.order-status'));
}, 'paypal-pos.order-sync.refund-factory' => static function (C $container): PurchaseRefundFactory {
    return new PurchaseRefundFactory($container->get('paypal-pos.order-sync.order-repository'), $container->get('paypal-pos.order-sync.order-defaults'), $container->get('paypal-pos.order-sync.order-taxes'), $container->get('paypal-pos.order-sync.payment-labels'), $container->get('paypal-pos.order-sync.logger'));
}, 'paypal-pos.order-sync.order-status' => static function (C $container): string {
    $settings = $container->get('paypal-pos.settings');
    $status = $settings->has('order_sync_status') ? (string) $settings->get('order_sync_status') : '';
    $status = (string) preg_replace('/^wc-/', '', $status);
    return array_key_exists("wc-{$status}", wc_get_order_statuses()) ? $status : 'completed';
}, 'paypal-pos.order-sync.backfill' => static function (C $container): PurchaseBackfill {
    return new PurchaseBackfill($container->get('inpsyde.queue.enqueue-job'), $container->get('paypal-pos.order-sync.enabled'), $container->get('paypal-pos.order-sync.is-onboarded'), $container->get('paypal-pos.setup-info'), $container->get('paypal-pos.order-sync.setting.yes')('order_sync_auto_recovery'), $container->get('paypal-pos.order-sync.setting.yes')('order_sync_import_on_setup'));
}, 'paypal-pos.order-sync.backfill-tools' => static function (C $container): BackfillTools {
    return new BackfillTools($container->get('paypal-pos.order-sync.backfill'));
}, 'paypal-pos.order-sync.setting.yes' => static function (C $container): callable {
    $settings = $container->get('paypal-pos.settings');
    // Yes/no settings default to yes until saved.
    return static fn(string $key): bool => !$settings->has($key) || $settings->get($key) !== 'no';
}, 'paypal-pos.order-sync.settings-filter' => static function (): OrderSyncSettingsFilter {
    return new OrderSyncSettingsFilter();
}, 'paypal-pos.order-sync.payment-types' => static function (C $container): array {
    $settings = $container->get('paypal-pos.settings');
    $types = $settings->has('order_sync_payment_types') ? (array) $settings->get('order_sync_payment_types') : [];
    return array_values(array_filter(array_map('strval', $types), static fn(string $type): bool => $type !== ''));
}, 'paypal-pos.order-sync.purchase-filter' => static function (C $container): PurchaseFilter {
    return new PurchaseFilter($container->get('paypal-pos.order-sync.payment-labels'), $container->get('paypal-pos.order-sync.payment-types'));
}, 'paypal-pos.order-sync.enabled' => static function (C $container): bool {
    $settings = $container->get('paypal-pos.settings');
    return !$settings->has('order_sync_enabled') || $settings->get('order_sync_enabled') === OrderSyncMode::ENABLED;
}, 'paypal-pos.order-sync.purchase-created-handler' => static function (C $container): PurchaseCreatedHandler {
    return new PurchaseCreatedHandler($container->get('inpsyde.queue.enqueue-job'), $container->get('paypal-pos.order-sync.logger'), $container->get('paypal-pos.order-sync.enabled'));
}, 'paypal-pos.order-sync.logger' => static function (): LoggerInterface {
    return new NullLogger();
}, 'paypal-pos.order-sync.is-onboarded' => static function (C $container): callable {
    return static function () use ($container): bool {
        return $container->get('inpsyde.state-machine')->currentState()->name() === OnboardingState::ONBOARDING_COMPLETED;
    };
}];
