<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Image;

use UnexpectedValueException;

interface ImageFormatRetrieverInterface
{
    /**
     * Determines the format of the given image.
     * @param string $url
     * @return string One of ImageFormat values
     * @throws UnexpectedValueException If failed to determine type.
     */
    public function determineImageFormat(string $url): string;
}
