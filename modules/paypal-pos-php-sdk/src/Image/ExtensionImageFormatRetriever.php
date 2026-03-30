<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Image;

use UnexpectedValueException;
/**
 * Uses file extension to determine image type.
 */
class ExtensionImageFormatRetriever implements ImageFormatRetrieverInterface
{
    public function determineImageFormat(string $url): string
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (!$path) {
            throw new UnexpectedValueException('Failed to parse image URL');
        }
        $ext = pathinfo($path, \PATHINFO_EXTENSION);
        if (!$ext) {
            throw new UnexpectedValueException('Failed to parse image URL');
        }
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                return ImageFormat::JPEG;
            case 'png':
                return ImageFormat::PNG;
            case 'tif':
            case 'tiff':
                return ImageFormat::TIFF;
            case 'gif':
                return ImageFormat::GIF;
            case 'bmp':
                return ImageFormat::BMP;
            default:
                throw new UnexpectedValueException("Unsupported image type {$ext}");
        }
    }
}
