<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Common\Exception;

use Syde\Vendor\Zettle\Http\Client\Exception\HttpException;
/**
 * Redirect location cannot be chosen.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class MultipleRedirectionException extends HttpException
{
}
