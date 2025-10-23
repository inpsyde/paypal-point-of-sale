<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Cli;

use Syde\PayPal\PointOfSale\Auth\Exception\AuthenticationException;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\RegisteredWebhook;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\ZettleWebhook;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Subscriptions;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\Webhooks\WebhookRegistration;
use Syde\PayPal\PointOfSale\Webhooks\WebhookStorageInterface;
use Symfony\Component\Uid\Uuid;
use WP_CLI;

use function WP_CLI\Utils\format_items;

class WebhookCommand
{

    /**
     * @var Subscriptions
     */
    private $subscriptions;

    /**
     * @var callable
     */
    private $webhookStorage;

    /**
     * @var WebhookRegistration
     */
    private $webhookRegistration;

    public function __construct(
        Subscriptions $subscriptions,
        WebhookStorageInterface $webhookStorage,
        WebhookRegistration $webhookRegistration
    ) {
        $this->subscriptions = $subscriptions;
        $this->webhookStorage = $webhookStorage;
        $this->webhookRegistration = $webhookRegistration;
    }

    /**
     * Registers the webhook(s) needed by the Zettle WooCommerce integration
     *
     * ## EXAMPLES
     *
     *     wp zettle webhook register
     *
     * @when after_wp_load
     */
    public function register()
    {
        try {
            $webhook = $this->webhookStorage->fetch();

            $uri = (string) $webhook->destination();

            WP_CLI::log("Registering webhook to destination: {$uri}");

            $registered = $this->webhookRegistration->execute();

            $this->webhookStorage->persist($registered);
        } catch (ZettleRestException | WebhookException | AuthenticationException $exception) {
            WP_CLI::error($exception->getMessage());
        }

        $this->list();
    }

    /**
     * Lists all webhooks registered in the configured merchant account
     *
     * ## EXAMPLES
     *
     *     wp zettle webhook list
     *
     * @when after_wp_load
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function list()
    {
        try {
            $result = $this->subscriptions->list();

            $table = array_map(
                static function (ZettleWebhook $webhook): array {
                    $status = 'UNREGISTERED';

                    if ($webhook instanceof RegisteredWebhook) {
                        $status = $webhook->status();
                    }

                    return [
                        'uuid' => $webhook->uuid(),
                        'eventNames' => json_encode($webhook->eventNames()),
                        'status' => $status,
                        'destination' => (string) $webhook->destination(),
                    ];
                },
                $result
            );

            format_items('table', $table, ['uuid', 'eventNames', 'status', 'destination']);
        } catch (ZettleRestException | WebhookException $exception) {
            WP_CLI::error($exception->getMessage());
        }
    }

    /**
     * Deletes a webhook from Zettle
     *
     * ## OPTIONS
     *
     * <uuid>
     * : The webhook UUID
     *
     * ## EXAMPLES
     *
     *     wp zettle webhook delete
     *
     * @when after_wp_load
     */
    public function delete(array $args, array $assocArgs)
    {
        [$uuid] = $args;

        if (!Uuid::isValid($uuid)) {
            WP_CLI::error("Not a valid UUID: {$uuid}");
        }

        try {
            $this->subscriptions->delete($uuid);

            WP_CLI::success("Deleted webhook with UUID: {$uuid}");
        } catch (ZettleRestException $exception) {
            WP_CLI::error($exception->getMessage());
        }
    }
}
