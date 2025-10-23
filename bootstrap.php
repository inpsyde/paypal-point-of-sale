<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Dhii\Container\CachingContainer;
use Syde\Vendor\Zettle\Dhii\Container\CompositeCachingServiceProvider;
use Syde\Vendor\Zettle\Dhii\Container\DelegatingContainer;
use Syde\Vendor\Zettle\Dhii\Container\ProxyContainer;
use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Zettle\Dhii\Validation\ValidatorInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
return static function (string $appDir, bool $validate = \false): ContainerInterface {
    $modules = [];
    $classNames = require $appDir . '/modules.php';
    array_walk($classNames, static function (string $className) use (&$modules): void {
        $modules[] = new $className();
    });
    $providers = [];
    foreach ($modules as $module) {
        assert($module instanceof ModuleInterface);
        $providers[] = $module->setup();
    }
    $proxy = new ProxyContainer();
    $provider = new CompositeCachingServiceProvider($providers);
    $container = new CachingContainer(new DelegatingContainer($provider, $proxy));
    $proxy->setInnerContainer($container);
    if ($validate) {
        $requirementsValidator = $container->get('paypal-pos.requirements.validator');
        assert($requirementsValidator instanceof ValidatorInterface);
        $requirementsValidator->validate(null);
    }
    foreach ($modules as $module) {
        assert($module instanceof ModuleInterface);
        $module->run($proxy);
    }
    return $proxy;
};
