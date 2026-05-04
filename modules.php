<?php

namespace Syde\Vendor\Zettle;

use Syde\Vendor\Zettle\Inpsyde\Debug\InpsydeDebugModule;
use Syde\Vendor\Zettle\Inpsyde\Http\HttpClientModule;
use Syde\Vendor\Zettle\Inpsyde\Queue\QueueModule;
use Syde\Vendor\Zettle\Inpsyde\StateMachine\StateMachineModule;
use Syde\Vendor\Zettle\Inpsyde\WcEvents\WcEventsModule;
use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\WcStatusReportModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Assets\AssetsModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\AuthModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Logging\ZettleLoggingModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices\NoticesModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\OnboardingModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\PhpSdkModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PluginModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductDebug\ProductDebugModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\ProductSettingsModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue\ZettleQueueModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings\SettingsModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\SyncModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\WebhookModule;
return [InpsydeDebugModule::class, HttpClientModule::class, QueueModule::class, StateMachineModule::class, WcEventsModule::class, AssetsModule::class, AuthModule::class, ZettleLoggingModule::class, NoticesModule::class, OnboardingModule::class, PhpSdkModule::class, ProductDebugModule::class, ProductSettingsModule::class, ZettleQueueModule::class, SettingsModule::class, SyncModule::class, WebhookModule::class, WcStatusReportModule::class, PluginModule::class];
