<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter;

/**
 * Limits the given description in length
 *
 * @TODO: create injectable Filter instead of current Helper approach
 */
class DescriptionLengthFilter
{
    public const MAX_DESCRIPTION_SIZE = 1024;
    public const DEFAULT_TRIM_MARKER = '...';
    /**
     * Limits the given description in length according to given parameters
     *
     * @param string $description   Text/Description which will be trimmed
     * @param int $start            Start character position
     * @param int $max              End character position
     * @param string $trimMaker     Replacement for the rest of the characters
     *
     * @return string
     */
    public static function limitDescription(string $description, int $start = 0, int $max = self::MAX_DESCRIPTION_SIZE, string $trimMaker = self::DEFAULT_TRIM_MARKER): string
    {
        return mb_strimwidth($description, $start, $max, $trimMaker);
    }
}
