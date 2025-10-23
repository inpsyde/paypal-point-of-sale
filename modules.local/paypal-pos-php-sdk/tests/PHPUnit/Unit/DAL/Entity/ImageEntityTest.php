<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ConcreteImage;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class ImageEntityTest extends BrainMonkeyWpTestCase
{

    public function testCreateEntity(): void
    {
        $lookupKey = uniqid('foo_');
        $image = new ConcreteImage($lookupKey);

        $this->assertSame($lookupKey, $image->imageLookupKey());

        $this->assertSame(
            ConcreteImage::BASE_URL.'L/'.$lookupKey,
            $image->smallImageUrl()
        );
        $this->assertSame(
            ConcreteImage::BASE_URL.'o/'.$lookupKey,
            $image->largeImageUrl()
        );
    }
}
