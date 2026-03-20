<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Assets;

use Syde\PayPal\PointOfSale\Onboarding\SyncCollisionStrategy;
use Syde\PayPal\PointOfSale\Sync\Job\EnqueueProductSyncJob;
use Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\WipeRemoteProductsJob;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;

return [
    'paypal-pos.assets.sync-job-types' => static function (C $container): array {
        $jobTypes = [
            'prepare' => [
                EnqueueProductSyncJob::TYPE,
            ],
            'sync' => [
                ExportProductJob::TYPE,
            ],
        ];
        $settings = $container->get('paypal-pos.settings');

        if ($settings->has('sync_collision_strategy')) {
            $collisionStrategy = $settings->get('sync_collision_strategy');

            if ($collisionStrategy === SyncCollisionStrategy::WIPE) {
                $jobTypes['prepare'][] = WipeRemoteProductsJob::TYPE;
            }
        }

        return $jobTypes;
    },
    'paypal-pos.assets.should-enqueue.all' => static function (C $container): callable {
        return static function () use ($container): bool {
            return true;
        };
    },
    'paypal-pos.assets.should-enqueue.sync-module' => static function (C $container): callable {
        return static function () use ($container): bool {
            return $container->get('paypal-pos.assets.should-enqueue.all')();
        };
    },
];
