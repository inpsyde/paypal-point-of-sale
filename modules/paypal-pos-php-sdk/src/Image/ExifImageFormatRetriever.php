<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Image;

use UnexpectedValueException;
/**
 * Uses exif_imagetype to determine image type.
 */
class ExifImageFormatRetriever implements ImageFormatRetrieverInterface
{
    public function determineImageFormat(string $url): string
    {
        $type = exif_imagetype($url);
        if ($type === \false) {
            throw new UnexpectedValueException('Unknown image signature or incorrect address');
        }
        switch ($type) {
            case \IMAGETYPE_JPEG:
                return ImageFormat::JPEG;
            case \IMAGETYPE_PNG:
                return ImageFormat::PNG;
            case \IMAGETYPE_TIFF_II:
            case \IMAGETYPE_TIFF_MM:
                return ImageFormat::TIFF;
            case \IMAGETYPE_GIF:
                return ImageFormat::GIF;
            case \IMAGETYPE_BMP:
                return ImageFormat::BMP;
            default:
                throw new UnexpectedValueException(sprintf("Unsupported image type %d", (int) $type));
        }
    }
}
