<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Queue;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;

return [
    'inpsyde.queue.namespace' => static function (string $previous, C $container): string {
        return "zettle";
    },
];
