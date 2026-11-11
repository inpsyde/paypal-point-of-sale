<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

interface PayloadFactory
{
    public function fromArray(array $data): Payload;
}
