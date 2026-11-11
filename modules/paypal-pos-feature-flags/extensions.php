<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
return ['paypal-pos.feature-flags.properties' => static function (array $previous, C $container): array {
    if (!$container->get('paypal-pos.feature-flags.qa-site')) {
        return $previous;
    }
    return ['qaSite' => 'true'] + $previous;
}];
