<?php

declare(strict_types=1);

namespace Inpsyde\Http;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Inpsyde\Wp\HttpClient\Client as WpHttpClient;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use WP_Http;

return [
    'inpsyde.http-client.factory' =>
        static function (ContainerInterface $container): HttpClientFactory {
            return new HttpClientFactory($container->get('inpsyde.http-client.inner-client'));
        },
    'inpsyde.http-client' => static function (ContainerInterface $container): ClientInterface {
        return ($container->get('inpsyde.http-client.factory'))->withPlugins(
            ...$container->get('inpsyde.http-client.plugins')
        );
    },
    'inpsyde.http-client.wp-client' => static function (
        ContainerInterface $container
    ): ClientInterface {
        return new WpHttpClient(
            new WP_Http(),
            $container->get('inpsyde.http-client.request-factory'),
            $container->get('inpsyde.http-client.response-factory'),
            $container->get('inpsyde.http-client.stream-factory')
        );
    },
    'inpsyde.http-client.mode' => static function (): string {
        return (string) apply_filters('paypal-point-of-sale.http.client', 'wp');
    },
    'inpsyde.http-client.inner-client' => static function (
        ContainerInterface $container
    ): ClientInterface {
        $chosenClient = $container->get('inpsyde.http-client.mode');
        if ($chosenClient === 'wp') {
            return $container->get('inpsyde.http-client.wp-client');
        }
        return Psr18ClientDiscovery::find();
    },
    'inpsyde.http-client.uri-factory' => static function (): UriFactoryInterface {
        // scoping workaround, IZET-318
        // TODO: Remove as soon as we have a scoping mechanism in place
        if (!method_exists(Psr17FactoryDiscovery::class, 'findUriFactory')) {
            return Psr17FactoryDiscovery::findUrlFactory();
        }
        return Psr17FactoryDiscovery::findUriFactory();
    },
    'inpsyde.http-client.request-factory' => static function (): RequestFactoryInterface {
        return Psr17FactoryDiscovery::findRequestFactory();
    },
    'inpsyde.http-client.response-factory' => static function (): ResponseFactoryInterface {
        return Psr17FactoryDiscovery::findResponseFactory();
    },
    'inpsyde.http-client.stream-factory' => static function (): StreamFactoryInterface {
        return Psr17FactoryDiscovery::findStreamFactory();
    },
    'inpsyde.http-client.plugins' => static function (): array {
        return [];
    },

];
