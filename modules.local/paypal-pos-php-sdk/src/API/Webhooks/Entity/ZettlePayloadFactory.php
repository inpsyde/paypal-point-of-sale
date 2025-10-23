<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

class ZettlePayloadFactory implements PayloadFactory
{

    public function fromArray(array $data): Payload
    {
        $payload = json_decode($data['payload'], true);

        return new ZettlePayload(
            $data['eventName'],
            $data['organizationUuid'],
            $data['messageId'],
            $payload
        );
    }
}
