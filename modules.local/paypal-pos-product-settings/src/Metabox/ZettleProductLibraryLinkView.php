<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Metabox;

use MetaboxOrchestra\BoxInfo;
use MetaboxOrchestra\BoxView;

class ZettleProductLibraryLinkView implements BoxView
{
    private string $baseLink;

    public function __construct(string $baseLink)
    {
        $this->baseLink = $baseLink;
    }

    public function render(BoxInfo $info): string
    {
        ob_start(); ?>

        <a href="<?php
        // phpcs:ignore WordPress.Security.EscapeOutput
        echo $this->productLink($info['uuid']);
        ?>"
            target="_blank" rel="noreferrer noopener">
            View Product at PayPal Point of Sale
        </a>

        <?php return (string) ob_get_clean();
    }

    private function productLink(string $productUuid): string
    {
        return sprintf(
            '%1$s/%2$s',
            $this->baseLink,
            $productUuid
        );
    }
}
