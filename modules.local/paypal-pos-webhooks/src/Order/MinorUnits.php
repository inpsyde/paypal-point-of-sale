<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

/**
 * Converts Zettle minor-unit amounts (e.g. cents) to WooCommerce major units.
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
}
