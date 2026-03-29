<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Handler;

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;

interface WebhookHandler
{
    /**
     * @param Payload $payload
     *
     * @return bool
     */
    public function accepts(Payload $payload): bool;

    /**
     * @param Payload $payload
     *
     * @return mixed
     */
    public function handle(Payload $payload);
}
