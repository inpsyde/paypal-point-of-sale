<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Handler;

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;

class InventoryTrackingStartedHandler implements WebhookHandler
{
    /**
     * @inheritDoc
     */
    public function accepts(Payload $payload): bool
    {
        return $payload->eventName() === 'InventoryTrackingStarted';
    }

    /**
     * @inheritDoc
     */
    public function handle(Payload $payload): void
    {
        // TODO: Implement handle() method.
    }
}
