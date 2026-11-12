<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\Module;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Properties\PluginProperties;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags\FeatureFlagsModule;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags\Flags;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation\ValidatorInterface;
/*
 * $allModules loads every module regardless of its feature flag, e.g. so uninstalling cleans
 * up after modules that are off.
 */
return static function (string $pluginFile, bool $validate = \false, bool $allModules = \false): Package {
    // Must run before any service is resolved: some modules apply the filters from run().
    (new DeprecatedHooks())->register();
    $properties = PluginProperties::new($pluginFile);
    // Before the modules, so flags decide which modules are loaded.
    $flags = Flags::boot($properties->version());
    $package = Package::new($properties);
    $package->addModule(new FeatureFlagsModule($flags));
    $classNames = $flags->modules(require dirname($pluginFile) . '/modules.php', $allModules);
    foreach ($classNames as $className) {
        $module = new $className();
        assert($module instanceof Module);
        $package->addModule($module);
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
