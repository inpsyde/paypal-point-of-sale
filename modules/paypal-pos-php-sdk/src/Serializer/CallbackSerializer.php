<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Serializer;

class CallbackSerializer implements SerializerInterface
{
    /**
     * @var callable
     */
    private $callback;
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }
    public function serialize(object $entity, ?SerializerInterface $serializer = null): array
    {
        return (array) ($this->callback)($entity, $serializer);
    }
}
