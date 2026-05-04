<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
class ContainerAwareBuilder implements BuilderInterface
{
    private ContainerInterface $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    /**
     * @inheritDoc
     * phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null)
    {
        $concreteBuilder = $this->container->get($className);
        assert($concreteBuilder instanceof BuilderInterface);
        return $concreteBuilder->build($className, $payload, $builder ?? $this);
    }
}
