<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image;

class WordpressUrlProvider implements UrlProviderInterface
{
    /**
     * @param string $attachmentId
     *
     * @return string
     */
    public function provide(string $attachmentId): string
    {
        return str_replace(
            'http://',
            'https://',
            utf8_uri_encode(
                wp_get_attachment_image_url((int) $attachmentId, 'full')
            )
        );
    }
}
