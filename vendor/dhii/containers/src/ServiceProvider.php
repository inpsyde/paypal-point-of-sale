<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Dhii\Container;

use Syde\Vendor\Zettle\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
/**
 * A value object capable of providing services.
 *
 * @psalm-type Factory = callable(ContainerInterface): mixed
 * @psalm-type Extension = callable(ContainerInterface, mixed): mixed
 */
class ServiceProvider implements ServiceProviderInterface
{
    /** @var callable[] */
    protected array $factories;
    /**
     * @var callable[]
     */
    protected array $extensions;
    /**
     * @param callable[] $factories A map of service name to service factory.
     * @param callable[] $extensions A map of service name to service extension.
     */
    public function __construct(array $factories, array $extensions)
    {
        $this->factories = $factories;
        $this->extensions = $extensions;
    }
    /**
     * {@inheritDoc}
     */
    public function getFactories()
    {
        return $this->factories;
    }
    /**
     * {@inheritDoc}
     */
    public function getExtensions()
    {
        return $this->extensions;
    }
}
