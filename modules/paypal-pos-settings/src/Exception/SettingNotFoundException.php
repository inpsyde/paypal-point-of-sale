<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings\Exception;

use Exception;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\NotFoundExceptionInterface;
class SettingNotFoundException extends Exception implements NotFoundExceptionInterface
{
}
