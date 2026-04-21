<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Container;

use Psr\Container\ContainerInterface;

interface WritableContainerInterface extends ContainerInterface
{
    public function set(string $id, mixed $value): void;

    public function unset(string $key): void;
}
