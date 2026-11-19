<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Logger;

use Syde\Vendor\Zettle\Psr\Log\LoggerAwareInterface;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
interface LoggerProviderInterface extends LoggerAwareInterface
{
    /**
     * Get a logger instance on the object.
     *
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface;
}
