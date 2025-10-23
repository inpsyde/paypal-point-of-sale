<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Logger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

interface LoggerProviderInterface extends LoggerAwareInterface
{
    /**
     * Get a logger instance on the object.
     *
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface;
}
