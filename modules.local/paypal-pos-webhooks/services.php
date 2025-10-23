<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

use Inpsyde\Queue\Queue\Job\Job;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\RegisteredWebhook;
use Syde\PayPal\PointOfSale\Sync\Job\UnlinkProductJob;
use Syde\PayPal\PointOfSale\Webhooks\Cli\WebhookCommand;
use Syde\PayPal\PointOfSale\Webhooks\Handler\InventoryBalanceChangedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Handler\InventoryTrackingStartedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Handler\InventoryTrackingStoppedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Handler\LogHandler;
use Syde\PayPal\PointOfSale\Webhooks\Handler\ProductDeletedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Handler\WebhookHandler;
use Syde\PayPal\PointOfSale\Webhooks\Job\InventoryBalanceChangedJob;
use Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
use Syde\PayPal\PointOfSale\Webhooks\Rest\SignatureVerifier;
use Syde\PayPal\PointOfSale\Webhooks\Rest\WebhookListenerEndpoint;
use Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

$job = static function (string $type): string {
    return "zettle.job.{$type}";
};

return [
    $job(InventoryBalanceChangedJob::TYPE) => static function (C $container): Job {
        return new InventoryBalanceChangedJob(
            $container->get('inpsyde.wc-lifecycle-events.products.toggle'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    $job(WebhookRegistrationJob::TYPE) => static function (C $container): Job {
        return new WebhookRegistrationJob(
            $container->get('paypal-pos.webhook.register'),
            $container->get('paypal-pos.webhook.can-register')
        );
    },
    'paypal-pos.webhook.config' => static function (C $container): array {
        return [
            'uuid' => Uuid::v1(),
            'eventNames' => $container->get('paypal-pos.webhook.config.events'),
            'destination' => $container->get('paypal-pos.webhook.listener.url'),
            'contactEmail' => $container->get('paypal-pos.webhook.config.email'),

        ];
    },
    'paypal-pos.webhook.config.email' => static function (C $container): string {
        $fromEnv = getenv('IZETTLE_WEBHOOK_EMAIL');
        if ($fromEnv !== false) {
            return $fromEnv;
        }

        return 'zettle-webhooks@inpsyde.com';
    },
    'paypal-pos.webhook.config.events' => static function (): array {
        return [
            'ProductDeleted',
            'InventoryBalanceChanged',
            'InventoryTrackingStarted',
            'InventoryTrackingStopped',
        ];
    },
    'paypal-pos.webhook.listener.namespace' => static function (): string {
        return 'zettle/v1';
    },
    'paypal-pos.webhook.listener.route' => static function (): string {
        return '/webhook/listen';
    },
    'paypal-pos.webhook.listener.url' => static function (C $container): string {
        $namespace = $container->get('paypal-pos.webhook.listener.namespace');
        $route = $container->get('paypal-pos.webhook.listener.route');

        $url = str_replace(
            'http://',
            'https://',
            rest_url("{$namespace}{$route}")
        );

        $ngrokHost = getenv('NGROK_HOST');
        if ($ngrokHost) {
            $host = parse_url($url, PHP_URL_HOST);
            if (!$host) {
                return $url;
            }

            $url = str_replace($host, $ngrokHost, $url);
        }

        return $url;
    },
    'paypal-pos.webhook.listener' => static function (C $container): WebhookListenerEndpoint {
        return new WebhookListenerEndpoint(
            $container->get('paypal-pos.webhook.logger'),
            $container->get('paypal-pos.sdk.api.webhooks.payload.factory'),
            ...$container->get('paypal-pos.webhook.handlers')
        );
    },
    'paypal-pos.webhook.verifier' => static function (C $container): SignatureVerifier {
        return new SignatureVerifier(
            $container->get('paypal-pos.webhook.signing-key')
        );
    },
    'paypal-pos.webhook.registration' => static function (C $container): WebhookRegistration {
        $storage = $container->get('paypal-pos.webhook.storage');
        assert($storage instanceof WebhookStorageInterface);

        return new WebhookRegistration(
            $storage->fetch(),
            $container->get('paypal-pos.sdk.api.webhooks'),
            $container->get('paypal-pos.webhook.deletion'),
            $container->get('paypal-pos.webhook.logger')
        );
    },
    'paypal-pos.webhook.deletion' => static function (C $container): WebhookDeletion {
        $storage = $container->get('paypal-pos.webhook.storage');
        assert($storage instanceof WebhookStorageInterface);

        return new WebhookDeletion(
            $storage->fetch(),
            $container->get('paypal-pos.sdk.api.webhooks'),
            $container->get('paypal-pos.webhook.logger'),
            $container->get('paypal-pos.webhook.can-register')
        );
    },
    'paypal-pos.webhook.storage.option' => static function (C $container): string {
        return 'paypal-pos.webhook.listener';
    },
    'paypal-pos.webhook.storage' => static function (C $container): WebhookStorageInterface {
        return new WebhookStorage(
            $container->get('paypal-pos.sdk.api.webhooks.factory'),
            $container->get('paypal-pos.webhook.storage.container'),
            $container->get('paypal-pos.webhook.storage.option'),
            $container->get('paypal-pos.webhook.config')
        );
    },
    'paypal-pos.webhook.signing-key' => static function (C $container): string {
        $storage = $container->get('paypal-pos.webhook.storage');
        assert($storage instanceof WebhookStorageInterface);
        $webhook = $storage->fetch();

        if ($webhook instanceof RegisteredWebhook) {
            return $webhook->signingKey();
        }

        return '';
    },
    'paypal-pos.webhook.logger' => static function (C $container): LoggerInterface {
        return new NullLogger();
    },
    'paypal-pos.webhook.handlers.product-deleted' =>
        static function (C $container) use ($job): WebhookHandler {
            return new ProductDeletedHandler(
                $container->get($job(UnlinkProductJob::TYPE)),
                $container->get('paypal-pos.webhook.logger')
            );
        },
    'paypal-pos.webhook.handlers.inventory-balance-changed' =>
        static function (C $container) use ($job): WebhookHandler {
            return new InventoryBalanceChangedHandler(
                $container->get($job(InventoryBalanceChangedJob::TYPE)),
                $container->get('paypal-pos.webhook.logger'),
                $container->get('paypal-pos.sdk.id-map.variant'),
                $container->get('paypal-pos.sdk.integration-id')
            );
        },
    'paypal-pos.webhook.handlers' => static function (C $container): array {
        return [
            new LogHandler($container->get('paypal-pos.webhook.logger')),
            new InventoryTrackingStartedHandler(),
            new InventoryTrackingStoppedHandler(),
            $container->get('paypal-pos.webhook.handlers.inventory-balance-changed'),
        ];
    },
    'paypal-pos.webhook.register' => static function (C $container): callable {
        return static function () use ($container): void {
            $webhookStorage = $container->get('paypal-pos.webhook.storage');
            assert($webhookStorage instanceof WebhookStorageInterface);
            $registration = $container->get('paypal-pos.webhook.registration');
            assert($registration instanceof WebhookRegistration);
            $registered = $registration->execute();
            $webhookStorage->persist($registered);
        };
    },
    'paypal-pos.webhook.delete' => static function (C $container): callable {
        return static function () use ($container): void {
            $deletion = $container->get('paypal-pos.webhook.deletion');
            assert($deletion instanceof WebhookDeletion);
            $deletion->execute();
        };
    },
    'paypal-pos.webhook.can-register' => static function (C $container): callable {
        return static function () use ($container): bool {
            $noAuthStates = $container->get('paypal-pos.onboarding.no-auth-states');
            $stateMachine = $container->get('inpsyde.state-machine');
            return !in_array($stateMachine->currentState()->name(), $noAuthStates, true);
        };
    },
    'paypal-pos.webhook.cli' => static function (C $container): WebhookCommand {
        return new WebhookCommand(
            $container->get('paypal-pos.sdk.api.webhooks'),
            $container->get('paypal-pos.webhook.storage'),
            $container->get('paypal-pos.webhook.registration')
        );
    },
    'paypal-pos.webhook.bootstrap' => static function (C $container): Bootstrap {
        return new Bootstrap(
            $container->get('inpsyde.queue.enqueue-job'),
            $container->get('paypal-pos.webhook.delete')
        );
    },
];
