<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Assets;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\BaseAsset;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Rest\V1\ValidationEndpoint;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Counter\ProductSyncJobsCounter;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
return ['inpsyde.assets.registry' => static function (array $previous, C $container): array {
    $assetUri = rtrim(plugins_url('/assets/', __DIR__ . '/paypal-point-of-sale.php'), '/\\');
    if ($container->get('paypal-pos.assets.should-enqueue.all')()) {
        $previous[] = new Style('zettle-admin-style', "{$assetUri}/admin.css", BaseAsset::BACKEND);
        $previous[] = (new Script('zettle-admin-scripts', "{$assetUri}/admin-scripts.js", BaseAsset::BACKEND))->withLocalize('zettleAPIKeyCreation', static function () use ($container): array {
            $url = $container->get('paypal-pos.settings.account.link.api-key-creation-url');
            return ['url' => $url];
        })->withLocalize('zettleOnboardingValidationRules', static function () use ($container): array {
            return ['woocommerce_zettle_api_key' => ['required' => ['message' => __('Enter the API key.', 'paypal-point-of-sale')], 'remote' => ['url' => $container->get('paypal-pos.oauth.jwt.rest.url'), 'valueParamName', 'requestMethod' => 'GET', 'resultPropertyName' => 'result', 'skippedErrors' => [ValidationEndpoint::ERROR_WRITE_ONLY_PASSWORD_NOT_FILLED], 'nonce' => wp_create_nonce('wp_rest'), 'message' => __('The API key is not valid.', 'paypal-point-of-sale')]]];
        })->withLocalize('zettleDisconnection', static function () use ($container): array {
            return ['url' => $container->get('paypal-pos.onboarding.disconnect.endpoint.url'), 'dialogId' => $container->get('paypal-pos.settings.account.disconnect.id'), 'nonce' => wp_create_nonce('wp_rest')];
        });
    }
    if ($container->get('paypal-pos.assets.should-enqueue.sync-module')()) {
        $previous[] = (new Script('zettle-sync-scripts', "{$assetUri}/sync-scripts.js", BaseAsset::BACKEND))->withLocalize('zettleQueueProcessEndpoint', static function () use ($container): array {
            $jobTypes = $container->get('paypal-pos.assets.sync-job-types');
            $jobRepo = $container->get('inpsyde.queue.repository');
            assert($jobRepo instanceof JobRepository);
            $productSyncJobsCounter = $container->get('paypal-pos.onboarding.counter.product.sync');
            assert($productSyncJobsCounter instanceof ProductSyncJobsCounter);
            return ['nonce' => wp_create_nonce('wp_rest'), 'url' => $container->get('inpsyde.queue.rest.v1.endpoint.process.url'), 'requestArguments' => ['meta' => ['active' => \true, 'value' => ['zettle-onboarding' => \true]]], 'messages' => ['error' => __('There was an unexpected error while syncing products. Please check your logs and contact support.', 'paypal-point-of-sale'), 'confirmCancel' => __('Are you sure you want to cancel? You will be able to re-enter your sync settings and then start again.', 'paypal-point-of-sale'), 'finished' => __('Synchronization finished.', 'paypal-point-of-sale'), 'status' => ['prepare' => __('Preparing to sync products', 'paypal-point-of-sale'), 'sync' => __('Synchronization in progress', 'paypal-point-of-sale'), 'cleanup' => __('Cleaning up', 'paypal-point-of-sale')]], 'jobTypes' => $jobTypes];
        });
    }
    return $previous;
}];
