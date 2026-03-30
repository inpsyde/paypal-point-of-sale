<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Logging\Logger;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use WC_Logger_Interface;

/**
 * Class WooCommerceLogger
 * WooCommerce includes a logger interface, which is fully compatible to PSR-3,
 * but for some reason does not extend/implement it.
 *
 * This is a decorator that makes any WooCommerce Logger PSR-3-compatible
 *
 * @package Syde\PayPal\PointOfSale\Logging\Logger
 */
class WooCommerceLogger implements LoggerInterface
{
    use LoggerTrait;

    protected const LOG_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];
    private WC_Logger_Interface $wcLogger;

    public function __construct(WC_Logger_Interface $wcLogger)
    {
        $this->wcLogger = $wcLogger;
    }

    /**
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     *
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        if (!in_array($level, self::LOG_LEVELS, true)) {
            throw new InvalidArgumentException("Unknown log level " . esc_html($level));
        }

        if (!isset($context['source'])) {
            $context['source'] = 'paypal-point-of-sale';
        }

        $interpolatedMessage = is_string($message)
            ? $this->interpolate($message, $this->getReplacements($context))
            : $message;

        $this->wcLogger->log($level, $interpolatedMessage, $context);
    }

    /**
     * Interpolates the given values into the message placeholders.
     * based on
     * {@link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message}
     */
    protected function interpolate(string $message, array $replace): string
    {
        return strtr($message, $replace);
    }

    /**
     * Builds replacements list (for interpolate()) from the context values.
     * based on
     * {@link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message}
     * @param array $context
     * @return array
     */
    protected function getReplacements(array $context = []): array
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_string($key)) {
                continue;
            }
            // check that the value can be casted to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $val;
            }
        }
        return $replace;
    }
}
