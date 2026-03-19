<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Dhii\Collection;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as BaseContainerInterface;
/**
 * Something that can retrieve and determine the existence of a value by key.
 */
interface ContainerInterface extends HasCapableInterface, BaseContainerInterface
{
}
