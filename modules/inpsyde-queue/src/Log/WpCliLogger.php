<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Log;

use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\WP_CLI;
use Syde\Vendor\Zettle\WP_CLI\ExitException;
class WpCliLogger implements LoggerInterface
{
    /**
     * @inheritDoc
     */
    public function emergency($message, array $context = [])
    {
        WP_CLI::error($message, \false);
    }
    /**
     * @inheritDoc
     */
    public function alert($message, array $context = [])
    {
        WP_CLI::error($message, \false);
    }
    /**
     * @inheritDoc
     */
    public function critical($message, array $context = [])
    {
        WP_CLI::error($message, \false);
    }
    /**
     * @inheritDoc
     */
    public function error($message, array $context = [])
    {
        try {
            WP_CLI::error($message, \false);
        } catch (ExitException $exception) {
            WP_CLI::warning($message);
        }
    }
    /**
     * @inheritDoc
     */
    public function warning($message, array $context = [])
    {
        WP_CLI::warning($message);
    }
    /**
     * @inheritDoc
     */
    public function notice($message, array $context = [])
    {
        WP_CLI::log($message);
    }
    /**
     * @inheritDoc
     */
    public function info($message, array $context = [])
    {
        WP_CLI::log($message);
    }
    /**
     * @inheritDoc
     */
    public function debug($message, array $context = [])
    {
        WP_CLI::debug($message);
    }
    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        WP_CLI::log($message);
    }
}
