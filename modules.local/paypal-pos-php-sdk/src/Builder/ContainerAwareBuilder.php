<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Psr\Container\ContainerInterface;

class ContainerAwareBuilder implements BuilderInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null)
    {
        $concreteBuilder = $this->container->get($className);
        assert($concreteBuilder instanceof BuilderInterface);

        return $concreteBuilder->build($className, $payload, $builder ?? $this);
    }
}
