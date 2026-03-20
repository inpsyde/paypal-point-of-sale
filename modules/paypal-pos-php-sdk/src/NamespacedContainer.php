<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk;

use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
/**
 * Class NamespacedContainer
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk
 */
class NamespacedContainer implements ContainerInterface
{
    /**
     * @var string
     */
    private $namespace;
    /**
     * @var ContainerInterface
     */
    private $base;
    public function __construct(string $namespace, ContainerInterface $base)
    {
        $this->namespace = $namespace;
        $this->base = $base;
    }
    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        return $this->base->get("{$this->namespace}.{$id}");
    }
    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return $this->base->has("{$this->namespace}.{$id}");
    }
}
