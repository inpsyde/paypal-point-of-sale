<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation\ValidatorInterface;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Properties\PluginProperties;
return static function (string $pluginFile, bool $validate = \false): Package {
    $properties = PluginProperties::new($pluginFile);
    $package = Package::new($properties);
    $classNames = require dirname($pluginFile) . '/modules.php';
    foreach ($classNames as $className) {
        $package->addModule(new $className());
    }
    $package->build();
    if ($validate) {
        $requirementsValidator = $package->container()->get('paypal-pos.requirements.validator');
        assert($requirementsValidator instanceof ValidatorInterface);
        $requirementsValidator->validate(null);
    }
    $package->boot();
    return $package;
};
