<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image;

/**
 * Returns the path to a WordPress attachment on the server's filesystem.
 * Yes we are using filepaths as a Uniform Resource Locator, which is entirely valid
 * Though admittedly we are omitting the file:// access method
 */
class WordPressFilePathProvider implements UrlProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function provide(string $imageId): string
    {
        return utf8_uri_encode((string) get_attached_file((int) $imageId));
    }
}
