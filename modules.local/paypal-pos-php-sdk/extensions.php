<?php

declare(strict_types=1);

use Inpsyde\Debug\ExceptionFormatter;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Psr\Container\ContainerInterface as C;

return [
    'inpsyde.debug.exception-formatters' => static function (C $ctr, array $previous): array {
        $previous[ZettleRestException::class] = new class implements ExceptionFormatter {

            public function format(Throwable $exception): string
            {
                assert($exception instanceof ZettleRestException);

                return sprintf(
                    'ZettleRestException: %1$s%2$s%3$s%2$s%4$s%2$s'
                    . ' Violations: %5$s%2$s'
                    . ' Thrown in %6$s:%7$d%2$s'
                    . ' With data: %8$s%2$s'
                    . ' and payload: %9$s',
                    $exception->type(),
                    PHP_EOL,
                    $exception->getMessage(),
                    $exception->developerMessage(),
                    json_encode($exception->violations()) ?: '',
                    $exception->getFile(),
                    $exception->getLine(),
                    json_encode($exception->json()) ?: '',
                    json_encode($exception->payload()) ?: ''
                );
            }
        };

        return $previous;
    },
];
