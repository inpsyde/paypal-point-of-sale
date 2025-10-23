<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\UrlProviderInterface;

class UrlProviderFaker implements UrlProviderInterface
{
    /**
     * @inheritDoc
     */
    public function provide(string $image): string
    {
       return sprintf(
           'https://image.izettle.com/image_%s',
           $image
       );
    }
}
