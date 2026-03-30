<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk;

use Psr\Container\ContainerInterface;

/**
 * Class NamespacedContainer
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk
 */
class NamespacedContainer implements ContainerInterface
{
    private string $namespace;

    private ContainerInterface $base;

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
        return $this->base->get("{$this->namespace}.$id");
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return $this->base->has("{$this->namespace}.$id");
    }
}
