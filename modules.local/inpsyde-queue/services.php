<?php

declare(strict_types=1);

namespace Inpsyde\Queue;

use Inpsyde\Queue\Db\QueueTable;
use Inpsyde\Queue\Log\ArrayLogger;
use Inpsyde\Queue\Log\WpCliLogger;
use Inpsyde\Queue\Processor\ProcessorBuilder;
use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\FileBasedLocker;
use Inpsyde\Queue\Queue\ItemsCountStopper;
use Inpsyde\Queue\Queue\Job\ContainerAwareJobRecordFactory;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\JobContainer;
use Inpsyde\Queue\Queue\Job\JobIterator;
use Inpsyde\Queue\Queue\Job\JobRecord;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Queue\Queue\Job\WpDbJobRepository;
use Inpsyde\Queue\Queue\Job\NullJob;
use Inpsyde\Queue\Queue\Locker;
use Inpsyde\Queue\Queue\Runner\AsyncRequestRunner;
use Inpsyde\Queue\Queue\Runner\AggregateRunner;
use Inpsyde\Queue\Queue\Runner\Runner;
use Inpsyde\Queue\Queue\Runner\WpCronRunner;
use Inpsyde\Queue\Queue\Runner\WpHeartbeatRunner;
use Inpsyde\Queue\Queue\Runner\WpShutdownRunner;
use Inpsyde\Queue\Queue\StoppableQueueWalker;
use Inpsyde\Queue\Queue\Stopper;
use Inpsyde\Queue\Queue\TimeStopper;
use Inpsyde\Queue\Rest\V1\EndpointInterface;
use Inpsyde\Queue\Rest\V1\ProcessEndpoint;
use Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use wpdb;

$wire = static function (string ...$parts): callable {
    $class = array_shift($parts);

    //phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
    return static function (C $container) use ($class, $parts) {
        return new $class(
            ...array_map(
                static function (string $key) use ($container) {
                    return $container->get($key);
                },
                $parts
            )
        );
    };
    //phpcs:enable
};
//phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
//phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
$scalar = static function ($thing): callable {
    return static function () use ($thing) {
        return $thing;
    };
};

//phpcs:enable

return [
    'inpsyde.queue.namespace' => $scalar('inpsyde'),
    'inpsyde.queue.failed.retry.count' => $scalar(3),
    'inpsyde.queue.table' => $wire(QueueTable::class, 'inpsyde.queue.namespace'),
    'inpsyde.queue.bootstrap' => $wire(Bootstrap::class, 'inpsyde.queue.table'),
    'inpsyde.queue.factory' => static function (C $container): JobRecordFactoryInterface {
        $prefix = "{$container->get('inpsyde.queue.namespace')}.job";
        $container = new JobContainer($container, $prefix);

        return new ContainerAwareJobRecordFactory($container);
    },
    'inpsyde.queue.wpdb' => static function (): wpdb {
        global $wpdb;

        return $wpdb;
    },
    'inpsyde.is-multisite' => static function (): bool {
        return is_multisite();
    },
    'inpsyde.queue.site' => static function (): int {
        return get_current_blog_id();
    },
    /**
     * Used for FileBasedLocker
     */
    'inpsyde.queue.temp-dir' => static function (): string {
        return (string) get_temp_dir();
    },
    'inpsyde.queue.repository' => $wire(
        WpDbJobRepository::class,
        'inpsyde.queue.wpdb',
        'inpsyde.queue.table',
        'inpsyde.queue.factory',
        'inpsyde.queue.logger'
    ),
    'inpsyde.queue.exception-handler' => static function (): callable {
        return static function (Throwable $exception) {
            //Silence. This is intended to be overwritten/extended by clients
        };
    },
    'inpsyde.queue.iterator' => $wire(JobIterator::class, 'inpsyde.queue.repository'),
    'inpsyde.queue.stopper.cli' => static function (C $container): Stopper {
        /**
         * We allow a considerable budget during wp-cli-based wp-cron calls,
         * but don't want to overdo it.
         * If WP_CLI is used manually, we allow infinite items to be processed
         * unless the command itself allows and makes use of custom settings
         */
        $cron = (defined('DOING_CRON') && DOING_CRON);

        return new ItemsCountStopper(
            $cron
                ? 1000000
                : -1
        );
    },
    'inpsyde.queue.stopper.web' => static function (C $container): Stopper {
        /**
         * Take max a third of the max. execution time.
         */
        $time = ini_get('max_execution_time');
        $time = !(int) $time
            ? 10
            : $time / 3;

        return new TimeStopper((float) $time);
    },
    'inpsyde.queue.stopper' => static function (C $container): Stopper {
        if (defined('WP_CLI') && WP_CLI) {
            return $container->get('inpsyde.queue.stopper.cli');
        }

        return $container->get('inpsyde.queue.stopper.web');
    },
    'inpsyde.queue.logger' => static function (C $container): LoggerInterface {
        if (defined('WP_CLI') && WP_CLI) {
            return new WpCliLogger();
        }

        return new NullLogger();
    },
    'inpsyde.queue.logger.array' => $wire(
        ArrayLogger::class,
        'inpsyde.queue.logger'
    ),
    /**
     * It feels natural to scope the locker option key under the current namespace.
     * But what if there are multiple instances of the queue module all executing at the same time?
     */
    'inpsyde.queue.locker.option' => static function (C $container): string {
        return "{$container->get('inpsyde.queue.namespace')}_queue_running";
    },
    'inpsyde.queue.locker.file.path' => static function (C $container): string {
        $namespace = $container->get('inpsyde.queue.namespace');
        $siteId = $container->get('inpsyde.queue.site');
        $tempDir = rtrim($container->get('inpsyde.queue.temp-dir'), '/');

        return "{$tempDir}/{$namespace}-queue-{$siteId}.lock";
    },
    'inpsyde.queue.locker.timeout' => static function (C $container): int {
        $namespace = strtoupper($container->get('inpsyde.queue.namespace'));
        $envTimeout = (int) getenv("{$namespace}_QUEUE_FILE_LOCKER_TIMEOUT");
        if ($envTimeout) {
            return $envTimeout;
        }

        return max((int) ini_get('max_execution_time'), 30);
    },
    'inpsyde.queue.locker' => static function (C $container): Locker {
        return new FileBasedLocker(
            $container->get('inpsyde.queue.locker.timeout'),
            $container->get('inpsyde.queue.locker.file.path')
        );
    },
    'inpsyde.queue.runner' => static function (C $container): Runner {
        return new AggregateRunner(
            ...$container->get('inpsyde.queue.runners')
        );
    },
    'inpsyde.queue.runners' => static function (C $container): array {
        $runners = [
            $container->get('inpsyde.queue.runner.heartbeat'),
            $container->get('inpsyde.queue.runner.wp-cron'),
        ];

        if ($container->get('inpsyde.queue.runner.can-run-on-shutdown')) {
            $runners[] = $container->get('inpsyde.queue.runner.shutdown');
        }

        return $runners;
    },
    'inpsyde.queue.runner.wp-cron' => static function (C $container): Runner {
        return new WpCronRunner(
            $container->get('inpsyde.queue.namespace')
        );
    },
    'inpsyde.queue.runner.async' => static function (C $container): Runner {
        $namespace = $container->get('inpsyde.queue.rest.namespace');
        $endpoint = $container->get('inpsyde.queue.rest.v1.endpoint.process');
        $restPath = $namespace . $endpoint->route();

        return new AsyncRequestRunner($restPath);
    },
    'inpsyde.queue.runner.shutdown' => static function (C $container): Runner {
        return new WpShutdownRunner();
    },
    'inpsyde.queue.runner.can-run-on-shutdown' => static function (C $container): bool {
        return is_admin() && !wp_doing_ajax(); // do not slow down customers, ajax requests, etc.
    },
    'inpsyde.queue.runner.heartbeat' => static function (C $container): Runner {
        return new WpHeartbeatRunner($container->get('inpsyde.queue.runner.shutdown'));
    },
    'inpsyde.queue.walker' => $wire(
        StoppableQueueWalker::class,
        'inpsyde.queue.iterator',
        'inpsyde.queue.stopper'
    ),
    'inpsyde.queue.network-state.factory' => static function (C $container): callable {
        return static function (): NetworkState {
            return NetworkState::create();
        };
    },
    'inpsyde.queue.processor' => static function (C $container): QueueProcessor {
        $builder = new ProcessorBuilder($container->get('inpsyde.queue.factory'));

        return $builder
            ->withRepository($container->get('inpsyde.queue.repository'))
            ->withLocker($container->get('inpsyde.queue.locker'))
            ->withLogger($container->get('inpsyde.queue.logger'))
            ->withNetworkSupport($container->get('inpsyde.is-multisite'))
            ->withExceptionHandler($container->get('inpsyde.queue.exception-handler'))
            ->withMaxRetriesCount($container->get('inpsyde.queue.failed.retry.count'))
            ->build();
    },
    'inpsyde.queue.create-job-record' => static function (C $container): callable {
        return static function (
            string $type,
            array $args = [],
            ?int $siteId = null
        ) use ($container): JobRecord {
            $factory = $container->get('inpsyde.queue.factory');
            assert($factory instanceof JobRecordFactoryInterface);

            return $factory->fromData(
                $type,
                Context::fromArray($args, $siteId)
            );
        };
    },
    'inpsyde.queue.add-job-record' => static function (C $container): callable {
        return static function (
            JobRecord $jobRecord,
            ?JobRepository $repository = null
        ) use ($container) {
            /**
             * @var JobRepository $jobRepository
             */
            $repository = $repository ?? $container->get('inpsyde.queue.repository');
            assert($repository instanceof JobRepository);
            $repository->add($jobRecord);
        };
    },
    'inpsyde.queue.enqueue-job' => static function (C $container): callable {
        return static function (
            string $type,
            array $args = [],
            ?int $siteId = null,
            ?JobRepository $repository = null
        ) use ($container): void {
            $createJobRecord = $container->get('inpsyde.queue.create-job-record');
            $jobRecord = $createJobRecord($type, $args, $siteId);

            if ($jobRecord->job() instanceof NullJob) {
                return;
            }

            $addJobRecord = $container->get('inpsyde.queue.add-job-record');
            $addJobRecord($jobRecord, $repository);
        };
    },
    'inpsyde.queue.rest.namespace' => static function (C $container): string {
        $namespace = $container->get('inpsyde.queue.namespace');
        $processEndpoint = $container->get('inpsyde.queue.rest.v1.endpoint.process');

        return "{$namespace}-queue/{$processEndpoint->version()}";
    },
    'inpsyde.queue.rest.v1.endpoint.meta-callback' => static function (C $container): callable {
        return static function (array $meta = []): array {
            return $meta;
        };
    },
    'inpsyde.queue.rest.v1.endpoint.process' => static function (C $container): EndpointInterface {
        return new ProcessEndpoint(
            new ProcessorBuilder($container->get('inpsyde.queue.factory')),
            $container->get('inpsyde.queue.repository'),
            $container->get('inpsyde.queue.locker'),
            $container->get('inpsyde.queue.logger.array'),
            $container->get('inpsyde.queue.rest.v1.endpoint.meta-callback'),
            $container->get('inpsyde.is-multisite'),
            $container->get('inpsyde.queue.failed.retry.count')
        );
    },
    'inpsyde.queue.rest.v1.endpoint.process.url' => static function (C $container): string {
        $processEndpoint = $container->get('inpsyde.queue.rest.v1.endpoint.process');
        assert($processEndpoint instanceof EndpointInterface);

        return rest_url(
            $container->get('inpsyde.queue.rest.namespace') . $processEndpoint->route()
        );
    },
];
