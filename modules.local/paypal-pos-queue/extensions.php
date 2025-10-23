<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Queue;

use Psr\Container\ContainerInterface as C;

return [
    'inpsyde.queue.namespace' => static function (C $container, string $previous): string {
        return "zettle";
    },
];
