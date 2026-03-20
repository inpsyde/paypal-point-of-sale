<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Settings\Exception;

use Exception;
use Syde\Vendor\Zettle\Psr\Container\NotFoundExceptionInterface;

class SettingNotFoundException extends Exception implements NotFoundExceptionInterface
{

}
