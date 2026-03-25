<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Logging;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Logging\Logger\WonoLogger;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Logging\Logger\WooCommerceLogger;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Operator\Option\OptionOperatorInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Psr\Log\NullLogger;
use WC_Logger;
return ['paypal-pos.logger.woocommerce.enabled' => static function (C $container): bool {
    return !(bool) getenv('IZETTLE_LOGGING_DISABLE_WOOOCOMMERCE');
}, 'paypal-pos.logger.woocommerce' => static function (C $container): LoggerInterface {
    if (!$container->get('paypal-pos.logger.woocommerce.enabled')) {
        return new NullLogger();
    }
    if (!class_exists(WC_Logger::class)) {
        return new NullLogger();
    }
    return new WooCommerceLogger(wc_get_logger());
}, 'paypal-pos.logger.wonolog.enabled' => static function (): bool {
    return !(bool) getenv('IZETTLE_LOGGING_DISABLE_WONOLOG');
}, 'paypal-pos.logger.wonolog.channel' => static function (): string {
    $channel = getenv('IZETTLE_LOGGING_WONOLOG_CHANNEL');
    if (empty($channel)) {
        $channel = 'DEBUG';
    }
    return $channel;
}, 'paypal-pos.logger.wonolog' => static function (C $container): LoggerInterface {
    if (!$container->get('paypal-pos.logger.wonolog.enabled')) {
        return new NullLogger();
    }
    if (!function_exists('has_action') || !has_action('wonolog.log')) {
        return new NullLogger();
    }
    $channel = $container->get('paypal-pos.logger.wonolog.channel');
    return new WonoLogger($channel);
}];
