<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

return [
    'inpsyde.debug.proxy-factory' => static function (C $ctr): DebugProxyFactory {
        return new DebugProxyFactory(
            ...$ctr->get('inpsyde.debug.exception-handlers')
        );
    },
    'inpsyde.debug.exception-handler' => static function (C $ctr): ExceptionHandler {
        return new CompositeExceptionHandler(...$ctr->get('inpsyde.debug.exception-handlers'));
    },
    'inpsyde.debug.exception-handlers' => static function (C $ctr): array {
        return [
            $ctr->get('inpsyde.debug.exception-handler.logging'),
        ];
    },
    'inpsyde.debug.exception-handler.logging' => static function (C $ctr): ExceptionHandler {
        return new LogExceptionHandler(
            $ctr->get('inpsyde.debug.logger'),
            $ctr->get('inpsyde.debug.exception-formatter'),
            $ctr->get('inpsyde.debug.exception-log-levels')
        );
    },
    'inpsyde.debug.exception-log-levels' => static function (C $ctr): array {
        return [];
    },
    'inpsyde.debug.exception-formatter' => static function (C $ctr): ExceptionFormatter {
        return new TypeDelegatingFormatter(
            $ctr->get('inpsyde.debug.exception-formatter.fallback'),
            $ctr->get('inpsyde.debug.exception-formatters')
        );
    },
    'inpsyde.debug.exception-formatter.fallback' => static function (C $ctr): ExceptionFormatter {
        return new FallbackFormatter(
            $ctr->get('inpsyde.debug.is-debug-mode')
        );
    },
    'inpsyde.debug.exception-formatters' => static function (C $ctr): array {
        return [];
    },
    'inpsyde.debug.logger' => static function (C $ctr): LoggerInterface {
        return new NullLogger();
    },
    'inpsyde.debug.is-debug-mode' => static function (C $ctr): bool {
        return defined('WP_DEBUG') && WP_DEBUG;
    },
];
