<?php

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

interface RegisteredWebhook extends Webhook
{

    public function status(): string;

    public function signingKey(): string;
}
