<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Common\Exception;

use Syde\Vendor\Zettle\Http\Client\Exception\HttpException;
/**
 * Thrown when there is a server error (5xx).
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class ServerErrorException extends HttpException
{
}
