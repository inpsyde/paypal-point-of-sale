<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Exception;

use Exception;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientExceptionInterface;
class InvalidTokenException extends Exception implements ClientExceptionInterface
{
}
