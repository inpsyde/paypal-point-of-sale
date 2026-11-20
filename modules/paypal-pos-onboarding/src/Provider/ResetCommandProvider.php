<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Provider;

use Exception;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
use WP_CLI;
class ResetCommandProvider implements Provider
{
    public function boot(C $container): bool
    {
        if (defined('WP_CLI') && \WP_CLI) {
            // Resolving the command may translate strings, and WordPress warns about translations loaded before init.
            add_action('init', static function () use ($container): void {
                try {
                    WP_CLI::add_command('zettle reset onboarding', $container->get('paypal-pos.onboarding.cli.reset.onboarding'));
                } catch (Exception $exception) {
                }
            });
        }
        return \true;
    }
}
