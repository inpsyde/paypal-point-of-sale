<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Settings\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class SettingNotFoundException extends Exception implements NotFoundExceptionInterface
{
}
