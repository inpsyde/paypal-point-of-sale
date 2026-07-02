<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Iterator\Attachment\FeaturedImageIterator;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class FeaturedImageIteratorTest extends BrainMonkeyWpTestCase
{

    public function testIterator()
    {
        $id = 524307891;
        $product = Mockery::mock(WC_Product::class);
        $product->shouldReceive('get_image_id')->andReturn($id);
        $testee = new FeaturedImageIterator($product);
        $result = iterator_to_array($testee);
        $this->assertSame([$id], $result);
    }
}
