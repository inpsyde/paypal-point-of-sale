<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Container;

use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
interface WritableContainerInterface extends ContainerInterface
{
    public function set(string $id, mixed $value): void;
    public function unset(string $key): void;
}
