<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Provider;

use Exception;
use Psr\Container\ContainerInterface as C;
use Syde\PayPal\PointOfSale\Onboarding\Cli\ResetOnboardingCommand;
use Syde\PayPal\PointOfSale\Provider;
use WP_CLI;

class ResetCommandProvider implements Provider
{
    private ResetOnboardingCommand $resetOnboardingCommand;

    /**
     * CliCommandProvider constructor.
     *
     * @param ResetOnboardingCommand $resetOnboardingCommand
     */
    public function __construct(ResetOnboardingCommand $resetOnboardingCommand)
    {
        $this->resetOnboardingCommand = $resetOnboardingCommand;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        if (defined('WP_CLI') && WP_CLI) {
            try {
                WP_CLI::add_command(
                    'zettle reset onboarding',
                    $this->resetOnboardingCommand
                );
            } catch (Exception $exception) {
            }
        }

        return true;
    }
}
