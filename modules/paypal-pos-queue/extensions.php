<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue;

use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContainerAwareJobRecordFactory;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobContainer;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
return [
    'inpsyde.queue.namespace' => static function (string $previous, C $container): string {
        return "zettle";
    },
    /**
     * Resolve job services under the plugin's "paypal-pos.job.*" naming convention,
     * while keeping the queue infrastructure (DB table, REST route) under the
     * "zettle" namespace above. By default the queue derives the job prefix from
     * inpsyde.queue.namespace; overriding the factory decouples the two.
     */
    'inpsyde.queue.factory' => static function (JobRecordFactoryInterface $previous, C $container): JobRecordFactoryInterface {
        return new ContainerAwareJobRecordFactory(new JobContainer($container, 'paypal-pos.job'));
    },
];
