<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth;

use Syde\Vendor\Zettle\Http\Client\Common\Plugin\HeaderSetPlugin;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
return ['inpsyde.http-client.plugins' => static function (array $previous, ContainerInterface $container): array {
    $previous[] = $container->get('paypal-pos.http-plug.plugin');
    if (getenv('IZETTLE_CHAOS_MONKEY_ENABLED') === '1') {
        $previous[] = $container->get('paypal-pos.http-plug.plugin.chaos-monkey');
    }
    $previous[] = new HeaderSetPlugin($container->get('paypal-pos.oauth.headers.partner-affiliation'));
    return $previous;
}, 'paypal-pos.settings.fields.registry' => static function (array $previous, ContainerInterface $container): array {
    return array_merge($previous, ['authentication' => ['title' => __('Authentication', 'paypal-point-of-sale'), 'type' => 'title', 'description' => __('Credentials needed for communicating with your PayPal Point of Sale store via its API', 'paypal-point-of-sale')], 'api_key' => ['title' => __('API key', 'paypal-point-of-sale'), 'type' => 'zettle-writeonly-password', 'description' => __('Enter the API key you created through PayPal Point of Sale.', 'paypal-point-of-sale'), 'desc_tip' => \true, 'default' => '', 'custom_attributes' => ['autocomplete' => 'off']]]);
}];
