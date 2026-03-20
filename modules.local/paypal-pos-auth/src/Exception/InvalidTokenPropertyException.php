<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\Exception;

use Exception;
use Syde\Vendor\Zettle\Psr\Container\NotFoundExceptionInterface;

class InvalidTokenPropertyException extends Exception implements NotFoundExceptionInterface
{

}
