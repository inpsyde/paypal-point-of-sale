<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Iterator\Attachment\GalleryImageIterator;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class GalleryImageIteratorTest extends BrainMonkeyWpTestCase
{

    public function testIterator()
    {
        $expected = [1, 2, 3, 4, 5];
        $product = Mockery::mock(WC_Product::class);
        $product->shouldReceive('get_gallery_image_ids')->andReturn($expected);
        $testee = new GalleryImageIterator($product);
        $result = iterator_to_array($testee);
        $this->assertSame($expected, $result);
    }
}
