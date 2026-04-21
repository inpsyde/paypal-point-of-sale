<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

use Syde\Vendor\Zettle\Psr\Http\Message\UriInterface;
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
