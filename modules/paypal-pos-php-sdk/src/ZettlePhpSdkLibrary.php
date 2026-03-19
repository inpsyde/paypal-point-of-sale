<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk;

use Syde\Vendor\Zettle\Dhii\Container\CompositeCachingServiceProvider;
use Syde\Vendor\Zettle\Dhii\Container\DelegatingContainer;
use Syde\Vendor\Zettle\Dhii\Container\ServiceProvider;
use Syde\Vendor\Zettle\Dhii\Modular\Module\Exception\ModuleExceptionInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
/**
 * // phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 */
class ZettlePhpSdkLibrary
{
    /**
     * @var DelegatingContainer
     */
    private $container;
    /**
     * @var CompositeCachingServiceProvider
     */
    private $provider;
    /**
     * @var PhpSdkModule
     */
    private $module;
    public function __construct(array $factories = [], array $extensions = [])
    {
        $this->module = new PhpSdkModule();
        $providers = [$this->module->setup()];
        $providers[] = new ServiceProvider($factories, $extensions);
        $this->provider = new CompositeCachingServiceProvider($providers);
        $this->container = new DelegatingContainer($this->provider);
    }
    /**
     * @throws ModuleExceptionInterface
     */
    public function initialize()
    {
        $this->module->run($this->container());
    }
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
