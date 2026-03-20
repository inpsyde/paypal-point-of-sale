<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Provider;

use Inpsyde\StateMachine\StateMachineInterface;
use Syde\PayPal\PointOfSale\Onboarding\Event\AuthCheck;
use Syde\PayPal\PointOfSale\Provider;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;

class OnboardingRenderProvider implements Provider
{

    /**
     * @var StateMachineInterface
     */
    private $stateMachine;

    public function __construct(StateMachineInterface $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        // when in onboarding, check if auth still successful
        add_action('inpsyde.zettle.onboarding.rendering-started', function (): void {
            $this->stateMachine->handle(new AuthCheck());
        });

        return true;
    }
}
