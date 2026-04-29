<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Webhook;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Subscriptions;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;

/**
 * Subscribes the given webhook to zettle.
 */
class WebhookRegistration
{
    private Webhook $local;

    private Subscriptions $subscriptions;

    private WebhookDeletion $webhookDeletion;

    private LoggerInterface $logger;

    public function __construct(
        Webhook $local,
        Subscriptions $subscriptions,
        WebhookDeletion $webhookDeletion,
        LoggerInterface $logger
    ) {

        $this->subscriptions = $subscriptions;
        $this->local = $local;
        $this->webhookDeletion = $webhookDeletion;
        $this->logger = $logger;
    }

    /**
     * Execute Registration of Webhooks, also Delete outdated Webhooks and create new ones
     *
     * @return Webhook
     *
     * @throws ZettleRestException
     * @throws WebhookException
     */
    public function execute(): Webhook
    {
        /**
         * We currently pipe all events through a single Listener, so the assumption is that
         * only one should exist remotely. Since there's currently no update logic implemented here,
         * we therefore delete every remote webhook...
         */
        $this->webhookDeletion->execute();

        /**
         * ...and finally register our new one
         */
        try {
            return $this->subscriptions->create($this->local);
        } catch (ZettleRestException | WebhookException $exception) {
            $this->logger->warning($exception->getMessage());

            throw $exception;
        }
    }
}
