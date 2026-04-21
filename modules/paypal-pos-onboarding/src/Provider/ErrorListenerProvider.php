<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Provider;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Listener\UnhandledErrorListener;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
class ErrorListenerProvider implements Provider
{
    private UnhandledErrorListener $unhandledErrorListener;
    public function __construct(UnhandledErrorListener $unhandledErrorListener)
    {
        $this->unhandledErrorListener = $unhandledErrorListener;
    }
    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_action('inpsyde.zettle.settings.output-failed', $this->unhandledErrorListener);
        return \true;
    }
}
