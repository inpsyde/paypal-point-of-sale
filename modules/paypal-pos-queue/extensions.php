<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue;

use Syde\Vendor\Zettle\Inpsyde\Queue\Bootstrap;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContainerAwareJobRecordFactory;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobContainer;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
return [
    'paypal-pos.onboarding.resettable.options' => static function (array $previous, C $container): array {
        return array_merge($previous, [$container->get('inpsyde.queue.schema.option')]);
    },
    'paypal-pos.onboarding.resettable.transients' => static function (array $previous, C $container): array {
        return array_merge($previous, [$container->get('inpsyde.queue.schema.option') . Bootstrap::RETRY_LOCK_SUFFIX]);
    },
    // Jobs can't be queued or run while columns are missing, e.g. without ALTER rights.
    'inpsyde.wc-status-report.items' => static function (array $items, C $container): array {
        $bootstrap = $container->get('inpsyde.queue.bootstrap');
        assert($bootstrap instanceof Bootstrap);
        $items[] = $container->get('inpsyde.wc-status-report.item-factory')->createReportItem(__('Queue table up to date', 'paypal-point-of-sale'), 'Queue table up to date', $bootstrap->isUpToDate() ? 'yes' : 'no');
        return $items;
    },
    'inpsyde.queue.namespace' => static function (string $previous, C $container): string {
        return "zettle";
    },
    /**
     * Resolve job services under the plugin's "paypal-pos.job.*" naming convention,
     * while keeping the queue infrastructure (DB table, REST route) under the
     * "zettle" namespace above.
     * By default, the queue derives the job prefix from inpsyde.queue.namespace;
     */
    'inpsyde.queue.factory' => static function (JobRecordFactoryInterface $previous, C $container): JobRecordFactoryInterface {
        return new ContainerAwareJobRecordFactory(new JobContainer($container, 'paypal-pos.job'));
    },
];
