<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Provider;

use Exception;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Cli\ResetOnboardingCommand;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
use Syde\Vendor\Zettle\WP_CLI;
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
        if (defined('Syde\Vendor\Zettle\WP_CLI') && WP_CLI) {
            try {
                WP_CLI::add_command('zettle reset onboarding', $this->resetOnboardingCommand);
            } catch (Exception $exception) {
            }
        }
        return \true;
    }
}
