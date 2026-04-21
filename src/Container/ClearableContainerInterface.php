<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Container;

interface ClearableContainerInterface
{
    public function clear(): void;
}
