<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductDebug;

use Syde\PayPal\PointOfSale\ProductDebug\Cli\ProductsCommand;
use Syde\PayPal\PointOfSale\ProductDebug\Listing\CustomColumn;
use Syde\PayPal\PointOfSale\ProductDebug\Rest\V1\EndpointInterface;
use Syde\PayPal\PointOfSale\ProductDebug\Rest\V1\ProductValidationEndpoint;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;

return [
    'paypal-pos.product.debug.listing.custom-column' =>
        static function (C $container): CustomColumn {
            return new CustomColumn(
                'zettle_synced'
            );
        },
    'paypal-pos.product.debug.cli.products' =>
        static function (C $container): ProductsCommand {
            return new ProductsCommand(
                $container->get('paypal-pos.sync.allowed-product-types'),
                $container->get('paypal-pos.sdk.id-map.product'),
                $container->get('paypal-pos.sdk.builder'),
                $container->get('paypal-pos.sdk.api.products'),
                $container->get('paypal-pos.sync.validator.product'),
                $container->get('paypal-pos.sync.status.matcher')
            );
        },
    'paypal-pos.product.debug.namespace' => static function (): string {
        return 'zettle';
    },
    'paypal-pos.product.debug.rest.namespace' => static function (C $container): string {
        $namespace = $container->get('paypal-pos.product.debug.namespace');
        $validateEndpoint = $container->get('paypal-pos.product.debug.rest.v1.endpoint.validate');

        return "{$namespace}-product-debug/{$validateEndpoint->version()}";
    },
    'paypal-pos.product.debug.rest.v1.endpoint.validate' =>
        static function (C $container): EndpointInterface {
            return new ProductValidationEndpoint(
                $container->get('paypal-pos.sync.validator.product')
            );
        },
    'paypal-pos.product.debug.rest.v1.endpoint.validate.url' =>
        static function (C $container): string {
            $endpoint = $container->get('paypal-pos.product.debug.rest.v1.endpoint.validate');

            return rest_url(
                $container->get('paypal-pos.product.debug.rest.namespace') . $endpoint->route()
            );
        },
];
