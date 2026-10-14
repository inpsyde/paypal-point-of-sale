<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Serializer;

interface SerializerInterface
{
    public function serialize(object $entity, ?SerializerInterface $serializer = null): array;
}
