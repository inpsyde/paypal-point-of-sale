<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

class CallbackBuilder implements BuilderInterface
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function build(string $className, mixed $payload, ?BuilderInterface $builder = null): mixed
    {
        $result = ($this->callback)($className, $payload, $builder);
        assert($result instanceof $className);

        return $result;
    }
}
