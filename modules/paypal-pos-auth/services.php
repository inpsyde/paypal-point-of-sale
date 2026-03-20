<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth;

use Syde\Vendor\Zettle\Http\Client\Common\Plugin;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\HTTPlug\ChaosMonkeyPlugin;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\HTTPlug\ZettleAuthPlugin;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Jwt\ParserFactory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Jwt\ParserFactoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Jwt\ParserInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\CredentialValidator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Grant\GrantType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Grant\JwtGrant;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\ZettleOAuthHeader;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\ContainerTokenStorage;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenFactory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\TokenPersistingAuthSuccessHandler;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Rest\V1\EndpointInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Rest\V1\ValidationEndpoint;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Validator\Validator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Validator\ValidatorInterface;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
$wire = static function (string ...$parts): callable {
    $class = array_shift($parts);
    //phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
    return static function (C $container) use ($class, $parts) {
        return new $class(...array_map(static function (string $key) use ($container) {
            return $container->get($key);
        }, $parts));
    };
    //phpcs:enable
};
return ['paypal-pos.oauth.token-storage.key' => static function (C $container): string {
    return 'api_token';
}, 'paypal-pos.oauth.token-storage' => static function (C $container): ContainerTokenStorage {
    return new ContainerTokenStorage(
        // Must be satisfied externally. Maybe throw a special exception?
        $container->get('paypal-pos.oauth.token-storage.container'),
        $container->get('paypal-pos.oauth.token-storage.key'),
        new TokenFactory()
    );
}, 'paypal-pos.oauth.authentication' => $wire(ZettleOAuthHeader::class, 'paypal-pos.oauth.token-storage'), 'paypal-pos.oauth.credentials.parent' => static function (C $container) {
    return null;
}, 'paypal-pos.oauth.credentials' => static function (C $container): ContainerInterface {
    return new CredentialsContainer($container->get('paypal-pos.oauth.jwt.parser'), [], $container->get('paypal-pos.oauth.credentials.parent'));
}, 'paypal-pos.oauth.auth-grant' => static function (C $container): GrantType {
    return $container->get('paypal-pos.oauth.grant.api');
}, 'paypal-pos.oauth.refresh-grant' => static function (C $container): GrantType {
    return $container->get('paypal-pos.oauth.grant.api');
}, 'paypal-pos.oauth.grant.api' => $wire(JwtGrant::class, 'paypal-pos.oauth.credentials', 'paypal-pos.oauth.jwt.parser', 'paypal-pos.oauth.client-id'), 'paypal-pos.http-plug.plugin' => static function (C $container): Plugin {
    return new ZettleAuthPlugin($container->get('paypal-pos.oauth.authentication'), static function (RequestInterface $request): bool {
        $host = $request->getUri()->getHost();
        $path = $request->getUri()->getPath();
        if (!preg_match('/.*\.izettle\.com/', $host)) {
            return \false;
        }
        if ($host === 'oauth.izettle.com' && $path !== '/users/me') {
            return \false;
        }
        return \true;
    }, $container->get('inpsyde.http-client.uri-factory'), $container->get('inpsyde.http-client.stream-factory'), $container->get('paypal-pos.oauth.auth-grant'), $container->get('paypal-pos.oauth.refresh-grant'), new TokenPersistingAuthSuccessHandler($container->get('paypal-pos.oauth.token-storage'), new TokenFactory()));
}, 'paypal-pos.http-plug.plugin.chaos-monkey' => static function (C $container): Plugin {
    return new ChaosMonkeyPlugin($container->get('inpsyde.http-client.response-factory'), $container->get('inpsyde.http-client.stream-factory'));
}, 'paypal-pos.auth.is-failed' => static function (C $container): bool {
    return (bool) get_option($container->get('paypal-pos.auth.is-failed.key'));
}, 'paypal-pos.auth.is-failed.key' => static function (): string {
    return 'paypal-point-of-sale.auth-failed';
}, 'paypal-pos.oauth.http-client-factory' => static function (C $container): AuthenticatedClientFactory {
    return new AuthenticatedClientFactory($container->get('inpsyde.http-client.factory'), $container->get('inpsyde.http-client.uri-factory'), $container->get('inpsyde.http-client.stream-factory'), $container->get('paypal-pos.oauth.jwt.parser'), $container->get('paypal-pos.oauth.headers.partner-affiliation'), $container->get('paypal-pos.oauth.client-id'));
}, 'paypal-pos.oauth.credential-validator' => static function (C $container): CredentialValidator {
    return new CredentialValidator($container->get('paypal-pos.oauth.http-client-factory'), $container->get('inpsyde.http-client.request-factory'));
}, 'paypal-pos.oauth.client-id' => static function (): string {
    return 'de149dc7-44b5-4390-ab64-88e301771f06';
}, 'paypal-pos.oauth.headers.partner-affiliation' => static function (C $container): array {
    return ['X-iZettle-Application-Id' => $container->get('paypal-pos.oauth.client-id')];
}, 'paypal-pos.oauth.jwt.parser' => static function (C $container): ParserInterface {
    $factory = $container->get('paypal-pos.oauth.jwt.parser.factory');
    assert($factory instanceof ParserFactoryInterface);
    return $factory->createParser();
}, 'paypal-pos.oauth.jwt.parser.factory' => static function (C $container): ParserFactoryInterface {
    return new ParserFactory();
}, 'paypal-pos.oauth.jwt.validator' => static function (C $container): ValidatorInterface {
    return new Validator($container->get('paypal-pos.oauth.jwt.parser'));
}, 'paypal-pos.oauth.jwt.namespace' => static function (): string {
    return 'zettle';
}, 'paypal-pos.oauth.jwt.rest.namespace' => static function (C $container): string {
    $namespace = $container->get('paypal-pos.oauth.jwt.namespace');
    $endpoint = $container->get('paypal-pos.oauth.jwt.rest.v1.endpoint.validate');
    return "{$namespace}-jwt/{$endpoint->version()}";
}, 'paypal-pos.oauth.jwt.rest.url' => static function (C $container): string {
    $namespace = $container->get('paypal-pos.oauth.jwt.rest.namespace');
    $endpoint = $container->get('paypal-pos.oauth.jwt.rest.v1.endpoint.validate');
    return rest_url("{$namespace}{$endpoint->route()}");
}, 'paypal-pos.oauth.jwt.rest.v1.endpoint.validate' => static function (C $container): EndpointInterface {
    return new ValidationEndpoint($container->get('paypal-pos.oauth.jwt.validator'), $container->get('paypal-pos.onboarding.settings.write-only-password-field-checker'));
}];
