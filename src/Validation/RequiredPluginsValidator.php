<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation;

use function is_plugin_active;
use function is_plugin_active_for_network;
/**
 * Checks that plugins are active.
 */
class RequiredPluginsValidator implements ValidatorInterface
{
    /**
     * @var array<string, string>
     */
    protected array $plugins;
    /**
     * @param array<string, string> $plugins Keys - paths like 'woocommerce/woocommerce.php',
     * values - human-friendly names.
     */
    public function __construct(array $plugins)
    {
        $this->plugins = $plugins;
    }
    /**
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function validate($value): void
    {
        if (!function_exists('is_plugin_active')) {
            /** @psalm-suppress MissingFile */
            require_once \ABSPATH . '/wp-admin/includes/plugin.php';
        }
        (new CallbackValidator(function (): ?string {
            $missingPlugins = array_filter(array_keys($this->plugins), static function (string $path): bool {
                return !is_plugin_active($path) && !is_plugin_active_for_network($path);
            });
            if (empty($missingPlugins)) {
                return null;
            }
            return sprintf($this->getErrorMessageTemplate($missingPlugins), implode(', ', array_map(function (string $key): string {
                return $this->plugins[$key];
            }, $missingPlugins)));
        }))->validate(null);
    }
    protected function getErrorMessageTemplate(array $missingPlugins): string
    {
        if (count($missingPlugins) === 1) {
            // translators: %1$s - missing plugin dependency, like "WooCommerce"
            return __('PayPal Point of Sale requires %1$s plugin to be active.', 'paypal-point-of-sale');
        }
        // translators: %1$s - missing plugin dependencies, like "WooCommerce, AnotherPlugin"
        return __('PayPal Point of Sale requires these plugins to be active: %1$s.', 'paypal-point-of-sale');
    }
}
