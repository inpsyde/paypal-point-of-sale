<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Dhii\Modular\Module\Exception;

use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleAwareInterface;
use Throwable;
/**
 * Represents an exception that is thrown in relation to a module.
 *
 * @since 0.2
 */
interface ModuleExceptionInterface extends Throwable, ModuleAwareInterface
{
}
