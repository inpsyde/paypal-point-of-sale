<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Provider;

use Syde\PayPal\PointOfSale\Onboarding\Listener\UnhandledErrorListener;
use Syde\PayPal\PointOfSale\Provider;
use Psr\Container\ContainerInterface as C;

class ErrorListenerProvider implements Provider
{
    /**
     * @var UnhandledErrorListener
     */
    private $unhandledErrorListener;

    public function __construct(
        UnhandledErrorListener $unhandledErrorListener
    ) {
        $this->unhandledErrorListener = $unhandledErrorListener;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_action(
            'inpsyde.zettle.settings.output-failed',
            $this->unhandledErrorListener
        );

        return true;
    }
}
