<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Serializer;

interface SerializerInterface
{
    /**
     * @param $entity
     * @param SerializerInterface|null $serializer
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     *
     * @return array
     */
    public function serialize($entity, ?SerializerInterface $serializer = null): array;
}
