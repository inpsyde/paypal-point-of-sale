<?php

declare(strict_types=1);

namespace Inpsyde\Queue;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Inpsyde\Queue\Cli\QueueCommand;
use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\Runner\Runner;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use WP_CLI;
use WP_REST_Server;

class QueueModule implements ModuleInterface
{

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(
            require __DIR__ . '/../services.php',
            []
        );
    }

    /**
     * @inheritDoc
     *
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
     */
    public function run(ContainerInterface $container): void
    {
        $namespace = $container->get('inpsyde.queue.namespace');

        add_action(
            "{$namespace}.queue.add-job-record",
            $container->get('inpsyde.queue.add-job-record')
        );

        add_action(
            "{$namespace}.queue.create-job",
            $container->get('inpsyde.queue.enqueue-job'),
            10,
            3
        );

        if (defined('WP_CLI') && WP_CLI) {
            /** @noinspection PhpParamsInspection */
            WP_CLI::add_command(
                "{$namespace} queue",
                new QueueCommand($container->get('inpsyde.queue.processor'))
            );
        }

        /**
         * Grab the Runner implementation and initialize it.
         * There is no need to rush here, so we'll just hook into 'init'
         */
        add_action(
            'init',
            static function () use ($container) {
                $queueProcessor = $container->get('inpsyde.queue.processor');
                assert($queueProcessor instanceof QueueProcessor);

                $runner = $container->get('inpsyde.queue.runner');
                assert($runner instanceof Runner);

                $runner->initialize($queueProcessor);
            }
        );

        add_action(
            'rest_api_init',
            static function () use ($container) {
                $processEndpoint = $container->get('inpsyde.queue.rest.v1.endpoint.process');

                register_rest_route(
                    $container->get('inpsyde.queue.rest.namespace'),
                    $processEndpoint->route(),
                    [
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => [$processEndpoint, 'handleRequest'],
                        'permission_callback' => [$processEndpoint, 'permissionCallback'],
                        'args' => $processEndpoint->args(),
                    ]
                );
            }
        );
    }
}
