<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductDebug;

use Exception;
use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface as C;
use WP_CLI;

class ProductDebugModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }

    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }

    /**
     * phpcs:disable Syde.Functions.FunctionLength.TooLong
     */
    public function run(C $container): bool
    {
        add_action(
            'rest_api_init',
            static function () use ($container) {
                $validateEndpoint = $container->get('paypal-pos.product.debug.rest.v1.endpoint.validate');

                register_rest_route(
                    $container->get('paypal-pos.product.debug.rest.namespace'),
                    $validateEndpoint->route(),
                    [
                        'methods' => $validateEndpoint->methods(),
                        'callback' => [$validateEndpoint, 'handleRequest'],
                        'permission_callback' => [$validateEndpoint, 'permissionCallback'],
                        'args' => $validateEndpoint->args(),
                    ]
                );
            }
        );

        $customColumn = $container->get('paypal-pos.product.debug.listing.custom-column');

        add_filter(
            'manage_edit-product_columns',
            static function ($columns) use ($customColumn) {
                if (!is_admin()) {
                    return $columns;
                }

                return $customColumn->add($columns);
            }
        );

        add_action(
            'manage_posts_custom_column',
            static function ($columnName) use ($customColumn) {
                if (!is_admin()) {
                    return;
                }

                $content = $customColumn->renderContent((string) $columnName, (int) get_the_ID());

                if (!empty($content)) {
                    echo wp_kses_post($content);
                }
            },
            10,
            3
        );

        if (defined('WP_CLI') && WP_CLI) {
            try {
                WP_CLI::add_command(
                    "zettle products",
                    $container->get('paypal-pos.product.debug.cli.products')
                );
            } catch (Exception $exception) {
            }
        }

        return true;
    }
}
