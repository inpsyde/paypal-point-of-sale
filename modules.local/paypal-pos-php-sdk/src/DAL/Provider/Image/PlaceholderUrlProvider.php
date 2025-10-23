<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image;

/**
 * Always returns the same specified URL when queried
 */
class PlaceholderUrlProvider implements UrlProviderInterface
{

    /**
     * @var string
     */
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @inheritDoc
     */
    public function provide(string $imageId): string
    {
        return utf8_uri_encode($this->url);
    }
}
