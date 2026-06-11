<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Zettle\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\TokenPersistorInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\TokenProviderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Rest\V1\EndpointInterface;
class AuthModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;
    /**
     * @inheritDoc
     */
    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }
    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        return require __DIR__ . '/../extensions.php';
    }
    /**
     * @inheritDoc
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     * phpcs:disable Syde.Functions.FunctionLength.TooLong
     */
    public function run(ContainerInterface $container): bool
    {
        /**
         * @var TokenProviderInterface&TokenPersistorInterface $tokenStorage
         */
        $tokenStorage = $container->get('paypal-pos.oauth.token-storage');
        add_action('inpsyde.zettle.settings.updated', static function (array $changed): void {
            if (empty($changed)) {
                return;
            }
            if (isset($changed['api_key'])) {
                do_action('inpsyde.zettle.credentials.updated', $changed);
            }
        });
        $authFailedKey = $container->get('paypal-pos.auth.is-failed.key');
        add_action('inpsyde.zettle.auth.failed', static function () use ($authFailedKey) {
            update_option($authFailedKey, \true);
        });
        add_action('inpsyde.zettle.auth.succeeded', static function () use ($authFailedKey) {
            delete_option($authFailedKey);
        });
        add_action('inpsyde.zettle.credentials.updated', static function (array $changed) use ($container, $authFailedKey) {
            $storage = $container->get('paypal-pos.oauth.token-storage');
            assert($storage instanceof TokenPersistorInterface);
            $storage->clear();
            delete_option($authFailedKey);
        });
        add_action('woocommerce_init', static function () {
        });
        add_action('inpsyde.zettle.oauth.token-received', static function (TokenInterface $token) use ($tokenStorage) {
            $tokenStorage->persist($token);
        });
        add_action('rest_api_init', static function () use ($container) {
            $endpoint = $container->get('paypal-pos.oauth.jwt.rest.v1.endpoint.validate');
            assert($endpoint instanceof EndpointInterface);
            register_rest_route($container->get('paypal-pos.oauth.jwt.rest.namespace'), $endpoint->route(), ['methods' => $endpoint->methods(), 'callback' => [$endpoint, 'handleRequest'], 'permission_callback' => [$endpoint, 'permissionCallback'], 'args' => $endpoint->args()]);
        });
        return \true;
    }
}
