<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

use Syde\Vendor\Zettle\Nyholm\Psr7\Uri;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
use Syde\Vendor\Zettle\Symfony\Component\Uid\Uuid;
class ZettleWebhookFactory implements WebhookFactory
{
    public function fromArray(array $data): Webhook
    {
        if (!Uuid::isValid((string) $data['uuid'])) {
            throw new WebhookException('Invalid UUID for Webhook', 0);
        }
        return $this->createInstance($data);
    }
    private function createInstance(array $data): Webhook
    {
        switch (\true) {
            case array_key_exists('signingKey', $data):
                return new RegisteredZettleWebhook((string) $data['uuid'], $data['eventNames'], new Uri($data['destination']), $data['contactEmail'], $data['status'], $data['signingKey']);
            default:
                return new ZettleWebhook((string) $data['uuid'], $data['eventNames'], new Uri($data['destination']), $data['contactEmail']);
        }
    }
}
