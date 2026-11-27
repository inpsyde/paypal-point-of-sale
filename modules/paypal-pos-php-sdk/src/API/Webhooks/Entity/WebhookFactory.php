<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
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
