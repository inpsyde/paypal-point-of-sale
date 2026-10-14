<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Queue;

use Inpsyde\Queue\Queue\Job\ContainerAwareJobRecordFactory;
use Inpsyde\Queue\Queue\Job\JobContainer;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Psr\Container\ContainerInterface as C;

return [
    'inpsyde.queue.namespace' => static function (string $previous, C $container): string {
        return "zettle";
    },

    /**
     * Resolve job services under the plugin's "paypal-pos.job.*" naming convention,
     * while keeping the queue infrastructure (DB table, REST route) under the
     * "zettle" namespace above.
     * By default, the queue derives the job prefix from inpsyde.queue.namespace;
     */
    'inpsyde.queue.factory' => static function (
        JobRecordFactoryInterface $previous,
        C $container
    ): JobRecordFactoryInterface {
        return new ContainerAwareJobRecordFactory(
            new JobContainer($container, 'paypal-pos.job')
        );
    },
];
