<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\SyncCollisionStrategy;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter\PriceFilter;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter\VatFilter;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\Event\ProductEventListenerRegistry;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
return ['paypal-pos.settings.fields.registry' => static function (ContainerInterface $container, array $previous): array {
    return array_merge($previous, ['sync_params' => ['title' => __('Sync Parameters', 'paypal-point-of-sale'), 'type' => 'title', 'description' => __('Sets up how and what to synchronize to your PayPal Point of Sale store', 'paypal-point-of-sale')], 'sync_price_strategy' => ['title' => __('Price synchronization', 'paypal-point-of-sale'), 'type' => 'select', 'default' => PriceSyncMode::ENABLED, 'description' => __('Whether or not to sync prices to PayPal Point of Sale', 'paypal-point-of-sale'), 'options' => [PriceSyncMode::ENABLED => __('Sync prices', 'paypal-point-of-sale'), PriceSyncMode::DISABLED => __('Don\'t sync prices', 'paypal-point-of-sale')]], 'sync_collision_strategy' => [
        'title' => __('Existing products', 'paypal-point-of-sale'),
        'type' => 'select',
        'default' => SyncCollisionStrategy::MERGE,
        'description' => __('Replace existing products or add WooCommerce products to existing ones', 'paypal-point-of-sale'),
        // The first Option will be used as default
        'options' => [SyncCollisionStrategy::MERGE => __('Add WooCommerce products', 'paypal-point-of-sale'), SyncCollisionStrategy::WIPE => __('Replace PayPal Point of Sale library', 'zettle-woocommerce')],
    ]]);
}, 'paypal-pos.sdk.filters' => static function (ContainerInterface $container, array $previous): array {
    $settings = $container->get('paypal-pos.settings');
    if (!$settings->has('sync_price_strategy')) {
        return $previous;
    }
    $priceSyncMode = $settings->get('sync_price_strategy');
    if ($priceSyncMode === PriceSyncMode::DISABLED) {
        $previous[] = new PriceFilter();
    }
    return $previous;
}, 'inpsyde.wc-lifecycle-events.products.listener-provider' => static function (ContainerInterface $container, ProductEventListenerRegistry $registry): ProductEventListenerRegistry {
    $registry->onPropertyChange('stock_quantity', $container->get('paypal-pos.sync.listener.stock-quantity'));
    $registry->onPropertyChange('manage_stock', $container->get('paypal-pos.sync.listener.manage-stock.simple'), $container->get('paypal-pos.sync.listener.manage-stock.variable'), $container->get('paypal-pos.sync.listener.manage-stock.variation'));
    $registry->onChange($container->get('paypal-pos.sync.listener.all-props'), $container->get('paypal-pos.sync.listener.not-syncable'), $container->get('paypal-pos.sync.listener.variation.parent-stock'), $container->get('paypal-pos.sync.listener.delete-variable-without-variation'));
    $registry->onTypeChange($container->get('paypal-pos.sync.listener.type-change.simple-to-variable'), $container->get('paypal-pos.sync.listener.type-change.variable-to-simple'));
    $registry->onDelete($container->get('paypal-pos.sync.listener.delete.variation'), $container->get('paypal-pos.sync.listener.depublish'));
    $registry->onDraft($container->get('paypal-pos.sync.listener.depublish'));
    $registry->onTrash($container->get('paypal-pos.sync.listener.depublish'));
    $registry->onPending($container->get('paypal-pos.sync.listener.depublish'));
    $registry->onPrivate($container->get('paypal-pos.sync.listener.depublish'));
    $registry->onHide($container->get('paypal-pos.sync.listener.depublish'));
    $registry->onPublish($container->get('paypal-pos.sync.listener.publish.variation'));
    return $registry;
}];
