<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle;

use Syde\Vendor\Zettle\Inpsyde\Debug\InpsydeDebugModule;
use Syde\Vendor\Zettle\Inpsyde\Http\HttpClientModule;
use Syde\Vendor\Zettle\Inpsyde\Queue\QueueModule;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineModule;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\WcEventsModule;
use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\WcStatusReportModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Assets\AssetsModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\AuthModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags\FlaggedModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags\Flags;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Logging\ZettleLoggingModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices\NoticesModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\OnboardingModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\OrderSyncModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\PosOrdersModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\PhpSdkModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PluginModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductDebug\ProductDebugModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\ProductSettingsModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue\ZettleQueueModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings\SettingsModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\SyncModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\WebhookModule;
/*
 * The modules in load order. The feature flags module is added by bootstrap.php, before these.
 *
 * Wrap a module in a FlaggedModule to make it active only while a flag is on, e.g.
 * `new FlaggedModule($flags, 'order-sync', new OrderSyncModule())`. Nothing else may depend on
 * its services, since they don't exist while the flag is off.
 */
return static fn(Flags $flags): array => [
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
