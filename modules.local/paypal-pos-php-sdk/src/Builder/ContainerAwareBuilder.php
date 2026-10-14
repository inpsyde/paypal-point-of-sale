<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Psr\Container\ContainerInterface;

class ContainerAwareBuilder implements BuilderInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function build(string $className, mixed $payload, ?BuilderInterface $builder = null): mixed
    {
        $concreteBuilder = $this->container->get($className);
        assert($concreteBuilder instanceof BuilderInterface);

        return $concreteBuilder->build($className, $payload, $builder ?? $this);
    }
}
