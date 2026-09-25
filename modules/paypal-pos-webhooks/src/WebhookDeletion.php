<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks;

use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Webhook;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Subscriptions;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
/**
 * Unsubscribes all our webhooks.
 */
class WebhookDeletion
{
    private Webhook $local;
    private Subscriptions $subscriptions;
    private LoggerInterface $logger;
    /**
     * @var callable
     */
    private $canManageWebhooks;
    public function __construct(Webhook $local, Subscriptions $subscriptions, LoggerInterface $logger, callable $canManageWebhooks)
    {
        $this->subscriptions = $subscriptions;
        $this->local = $local;
        $this->logger = $logger;
        $this->canManageWebhooks = $canManageWebhooks;
    }
    /**
     * Execute Deletion of registered Webhooks, only for this destination
     *
     * @return void
     *
     * @throws WebhookException|ZettleRestException
     */
    public function execute(): void
    {
        if (!($this->canManageWebhooks)()) {
            return;
        }
        /**
         * Fetch all existing Webhooks and filter out ones that do not point to our installation.
         */
        $list = $this->subscriptions->list();
        /**
         * Filter webhooks via destination, only use matching webhooks for current destination
         */
        $webhooks = array_filter($list, function (Webhook $remote): bool {
            return (string) $remote->destination() === (string) $this->local->destination();
        });
        foreach ($webhooks as $webhook) {
            assert($webhook instanceof Webhook);
            try {
                $this->subscriptions->delete((string) $webhook->uuid());
            } catch (ZettleRestException $exception) {
                $this->logger->error($exception->getMessage());
                throw $exception;
            }
        }
    }
}
