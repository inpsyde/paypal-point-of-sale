<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale;

use Dhii\Container\CachingContainer;
use Dhii\Container\CompositeCachingServiceProvider;
use Dhii\Container\DelegatingContainer;
use Dhii\Container\ProxyContainer;
use Dhii\Modular\Module\ModuleInterface;
use Dhii\Validation\ValidatorInterface;
use Psr\Container\ContainerInterface;

return static function (string $appDir, bool $validate = false): ContainerInterface {
    $modules = [];
    $classNames = require $appDir . '/modules.php';
    array_walk(
        $classNames,
        static function (string $className) use (&$modules): void {
            $modules[] = new $className();
        }
    );

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
