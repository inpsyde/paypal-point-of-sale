<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image;

interface ImageInterface
{
    /**
     * @return string
     */
    public function imageLookupKey(): string;
    /**
     * Image Dimensions: 560*560px
     *
     * @return string
     */
    public function smallImageUrl(): string;
    /**
     * Image Dimensions: 2000*2000px
     *
     * @return string
     */
    public function largeImageUrl(): string;
}
