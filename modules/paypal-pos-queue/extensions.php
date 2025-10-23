<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Queue;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
return ['inpsyde.queue.namespace' => static function (C $container, string $previous): string {
    return "zettle";
}];
