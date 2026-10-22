<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price;

/**
 * Converts between Zettle's minor-unit amounts (e.g. cents) and major units, using the
 * currency's ISO 4217 exponent.
 */
final class MinorUnits
{
    private const ZERO_DECIMAL = ['JPY', 'KRW', 'ISK', 'HUF', 'CLP', 'VND'];

    public static function exponent(string $currency): int
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? 0 : 2;
    }

    public static function toMajor(float $minor, string $currency): float
    {
        return $minor / (10 ** self::exponent($currency));
    }

    public static function toMinor(float $major, string $currency): int
    {
        return (int) round($major * (10 ** self::exponent($currency)));
    }
}
