<?php

declare(strict_types=1);

use Inpsyde\Debug\InpsydeDebugModule;
use Inpsyde\Http\HttpClientModule;
use Inpsyde\Queue\QueueModule;
use Inpsyde\StateMachine\StateMachineModule;
use Inpsyde\WcEvents\WcEventsModule;
use Inpsyde\WcStatusReport\WcStatusReportModule;
use Syde\PayPal\PointOfSale\Assets\AssetsModule;
use Syde\PayPal\PointOfSale\Auth\AuthModule;
use Syde\PayPal\PointOfSale\FeatureFlags\FlaggedModule;
use Syde\PayPal\PointOfSale\FeatureFlags\Flags;
use Syde\PayPal\PointOfSale\Logging\ZettleLoggingModule;
use Syde\PayPal\PointOfSale\Notices\NoticesModule;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingModule;
use Syde\PayPal\PointOfSale\OrderSync\OrderSyncModule;
use Syde\PayPal\PointOfSale\OrderSync\PosOrdersModule;
use Syde\PayPal\PointOfSale\PhpSdk\PhpSdkModule;
use Syde\PayPal\PointOfSale\PluginModule;
use Syde\PayPal\PointOfSale\ProductDebug\ProductDebugModule;
use Syde\PayPal\PointOfSale\ProductSettings\ProductSettingsModule;
use Syde\PayPal\PointOfSale\Queue\ZettleQueueModule;
use Syde\PayPal\PointOfSale\Settings\SettingsModule;
use Syde\PayPal\PointOfSale\Sync\SyncModule;
use Syde\PayPal\PointOfSale\Webhooks\WebhookModule;

/*
 * The modules in load order. The feature flags module is added by bootstrap.php, before these.
 *
 * Wrap a module in a FlaggedModule to make it active only while a flag is on, e.g.
 * `new FlaggedModule($flags, 'order-sync', new OrderSyncModule())`. Nothing else may depend on
 * its services, since they don't exist while the flag is off.
 */
return static fn (Flags $flags): array => [
    new InpsydeDebugModule(),
    new HttpClientModule(),
    new QueueModule(),
    new StateMachineModule(),
    new WcEventsModule(),
    new AssetsModule(),
    new AuthModule(),
    new ZettleLoggingModule(),
    new NoticesModule(),
    new OnboardingModule(),
    new PhpSdkModule(),
    new ProductDebugModule(),
    new ProductSettingsModule(),
    new ZettleQueueModule(),
    new SettingsModule(),
    new SyncModule(),
    new WebhookModule(),
    new PosOrdersModule(),
    // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- it's the arrow function's parameter
    new FlaggedModule($flags, 'order-sync', new OrderSyncModule()),
    new WcStatusReportModule(),
    new PluginModule(),
];
