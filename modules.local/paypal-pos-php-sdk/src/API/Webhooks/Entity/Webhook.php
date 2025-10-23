<?php

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

use Psr\Http\Message\UriInterface;

interface Webhook
{

    public function uuid(): string;

    public function contactEmail(): string;

    public function destination(): UriInterface;

    public function eventNames(): array;

    /**
     * @param array $eventNames
     */
    public function changeEventNames(array $eventNames): void;
}
