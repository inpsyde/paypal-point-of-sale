<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Logging\Logger;

use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Psr\Log\LoggerTrait;
use Syde\Vendor\Zettle\Psr\Log\LogLevel;
/**
 * Wraps log events in a 'do_action' call that Wonolog understands.
 *
 * @link https://github.com/inpsyde/Wonolog
 */
class WonoLogger implements LoggerInterface
{
    use LoggerTrait;
    /**
     * @var string the Monolog channel to use
     */
    private string $channel;
    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }
    /**
     * Used for translating PSR-3 LogLevels to the integer-based levels of Monolog.
     * There are constants for this in Monolog, but so far this would be the
     * only reason to actually depend on Monolog, so we just copied the values here
     */
    private const LEVELS = [LogLevel::EMERGENCY => 600, LogLevel::ALERT => 550, LogLevel::CRITICAL => 500, LogLevel::ERROR => 400, LogLevel::WARNING => 300, LogLevel::NOTICE => 250, LogLevel::INFO => 200, LogLevel::DEBUG => 100];
    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    public function log($level, $message, array $context = [])
    {
        do_action('wonolog.log', ['message' => $message, 'channel' => $this->channel, 'level' => self::LEVELS[$level], 'context' => $context]);
    }
}
