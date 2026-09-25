<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Filter;

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
     * Limits the given description in size (UTF-8 bytes) according to given parameters,
     * without splitting multibyte characters
     *
     * @param string $description   Text/Description which will be trimmed
     * @param int $start            Start byte position
     * @param int $max              Maximum size in bytes, including the trim marker
     * @param string $trimMaker     Replacement for the rest of the characters
     *
     * @return string
     */
    public static function limitDescription(
        string $description,
        int $start = 0,
        int $max = self::MAX_DESCRIPTION_SIZE,
        string $trimMaker = self::DEFAULT_TRIM_MARKER
    ): string {

        $description = mb_strcut($description, $start, null, 'UTF-8');

        if (strlen($description) <= $max) {
            return $description;
        }

        return mb_strcut($description, 0, $max - strlen($trimMaker), 'UTF-8') . $trimMaker;
    }
}
