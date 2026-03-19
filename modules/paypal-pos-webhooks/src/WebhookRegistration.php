<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Webhook;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Subscriptions;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
/**
 * Subscribes the given webhook to zettle.
 */
class WebhookRegistration
{
    /**
     * @var Webhook
     */
    private $local;
    /**
     * @var Subscriptions
     */
    private $subscriptions;
    /**
     * @var callable
     */
    private $webhookDeletion;
    /**
     * @var LoggerInterface
     */
    private $logger;
    public function __construct(Webhook $local, Subscriptions $subscriptions, WebhookDeletion $webhookDeletion, LoggerInterface $logger)
    {
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
        } catch (ZettleRestException|WebhookException $exception) {
            $this->logger->warning($exception->getMessage());
            throw $exception;
        }
    }
}
