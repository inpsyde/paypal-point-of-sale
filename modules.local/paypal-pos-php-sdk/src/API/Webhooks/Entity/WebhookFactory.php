<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;

interface WebhookFactory
{
    /**
     * @param array $data
     *
     * @return Webhook
     *
     * @throws WebhookException
     */
    public function fromArray(array $data): Webhook;
}
