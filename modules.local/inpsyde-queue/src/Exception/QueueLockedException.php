<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Exception;

use Exception;

class QueueLockedException extends Exception implements QueueException
{

}
