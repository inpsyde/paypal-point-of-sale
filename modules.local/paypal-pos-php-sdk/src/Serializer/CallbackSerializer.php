<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Serializer;

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

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function serialize($entity, ?SerializerInterface $serializer = null): array
    {
        return (array) ($this->callback)($entity, $serializer);
    }
}
