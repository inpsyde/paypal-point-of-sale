<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Exception;

use Exception;
class QueueLockedException extends Exception implements QueueException
{
}
