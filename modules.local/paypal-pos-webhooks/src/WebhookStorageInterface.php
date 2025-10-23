<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Webhook;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;

interface WebhookStorageInterface
{

    /**
     * Persist the Webhook instance
     *
     * @param Webhook $webhook
     *
     * @return bool
     */
    public function persist(Webhook $webhook): bool;

    /**
     * Fetch the Webhook instance from the persistence layer
     *
     * @return Webhook
     *
     * @throws WebhookException
     */
    public function fetch(): Webhook;

    /**
     *
     * @return bool
     */
    public function clear(): bool;
}
