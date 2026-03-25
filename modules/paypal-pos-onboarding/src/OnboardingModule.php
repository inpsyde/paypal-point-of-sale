<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
class OnboardingModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;
    public function services(): array
    {
        return array_merge(require __DIR__ . '/../services.php', require __DIR__ . '/../state-machine.php');
    }
    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }
    /**
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function run(C $container): bool
    {
        foreach ($container->get('paypal-pos.onboarding.provider') as $provider) {
            $provider->boot($container);
        }
        add_filter('woocommerce_settings_api_sanitized_fields_' . $container->get('paypal-pos.settings.wc-integration.id'), [$container->get('paypal-pos.onboarding.settings.value-filter.api-key'), 'filterSettingsValues']);
        add_action('rest_api_init', static function () use ($container) {
            $endpoint = $container->get('paypal-pos.onboarding.disconnect.endpoint');
            register_rest_route($container->get('paypal-pos.onboarding.rest.namespace'), $endpoint->route(), ['methods' => $endpoint->methods(), 'callback' => [$endpoint, 'handleRequest'], 'permission_callback' => [$endpoint, 'permissionCallback'], 'args' => $endpoint->args()]);
        });
        return \true;
    }
}
