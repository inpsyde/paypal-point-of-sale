<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
class FeatureFlagsModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;
    /**
     * @inheritDoc
     */
    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }
    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }
    /**
     * Code that checks flags through the filters instead of the service connects on its
     * first check, like the service does.
     */
    public function run(C $container): bool
    {
        $flags = $container->get('paypal-pos.feature-flags');
        assert($flags instanceof Flags);
        $flags->initialize();
        return \true;
    }
}
