<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
/**
 * Gives the other modules the flags that the bootstrap connected before loading them.
 */
class FeatureFlagsModule implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;
    public function __construct(private readonly Flags $flags)
    {
    }
    /**
     * @inheritDoc
     */
    public function services(): array
    {
        $flags = $this->flags;
        return ['paypal-pos.feature-flags' => static fn(): Flags => $flags];
    }
    /**
     * The logger is a service, so a configuration error from the boot is logged here.
     */
    public function run(C $container): bool
    {
        $error = $this->flags->configurationError();
        if ($error === null) {
            return \true;
        }
        // An error persists on every request until it is fixed.
        $key = 'paypal_pos_feature_flags_error_' . md5($error);
        if (get_transient($key) === \false) {
            set_transient($key, 1, \DAY_IN_SECONDS);
            $logger = $container->get('paypal-pos.logger');
            assert($logger instanceof LoggerInterface);
            $logger->error('Feature flags are not available: {message}', ['message' => $error]);
        }
        return \true;
    }
}
