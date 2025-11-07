<?php

use Inpsyde\Debug\InpsydeDebugModule;
use Inpsyde\Http\HttpClientModule;
use Inpsyde\Queue\QueueModule;
use Inpsyde\StateMachine\StateMachineModule;
use Inpsyde\WcEvents\WcEventsModule;
use Inpsyde\WcStatusReport\WcStatusReportModule;
use Syde\PayPal\PointOfSale\Assets\AssetsModule;
use Syde\PayPal\PointOfSale\Auth\AuthModule;
use Syde\PayPal\PointOfSale\Logging\ZettleLoggingModule;
use Syde\PayPal\PointOfSale\Notices\NoticesModule;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingModule;
use Syde\PayPal\PointOfSale\PhpSdk\PhpSdkModule;
use Syde\PayPal\PointOfSale\PluginModule;
use Syde\PayPal\PointOfSale\ProductDebug\ProductDebugModule;
use Syde\PayPal\PointOfSale\ProductSettings\ProductSettingsModule;
use Syde\PayPal\PointOfSale\Queue\ZettleQueueModule;
use Syde\PayPal\PointOfSale\Settings\SettingsModule;
use Syde\PayPal\PointOfSale\Sync\SyncModule;
use Syde\PayPal\PointOfSale\Webhooks\WebhookModule;

return [
    InpsydeDebugModule::class,
    HttpClientModule::class,
    QueueModule::class,
    StateMachineModule::class,
    WcEventsModule::class,
    AssetsModule::class,
    AuthModule::class,
    ZettleLoggingModule::class,
    NoticesModule::class,
    OnboardingModule::class,
    PhpSdkModule::class,
    ProductDebugModule::class,
    ProductSettingsModule::class,
    ZettleQueueModule::class,
    SettingsModule::class,
    SyncModule::class,
    WebhookModule::class,
    WcStatusReportModule::class,
    PluginModule::class,
];
