<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Iterator\Attachment\ChildrenImageIterator;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\expect;

/**
 * phpcs:disable
 */
class ChildrenImageIteratorTest extends BrainMonkeyWpTestCase
{

    /**
     * @param WC_Product $product
     *
     * @dataProvider defaultTestData
     */
    public function testIterator(array $childIds, array $expected)
    {
        $attachmentId = 1;
        foreach ($childIds as $id) {
            $variation = Mockery::mock(WC_Product::class);
            $variation->shouldReceive('get_image_id')->once()->andReturnUsing(
                function () use (&$attachmentId) {
                    return $attachmentId++;
                }
            );
            expect('wc_get_product')->once()->with($id)->andReturn($variation);
        }
        $mock = Mockery::mock(WC_Product::class);
        $mock->shouldReceive('get_children')->andReturn($childIds);
        $testee = new ChildrenImageIterator($mock);
        $result = iterator_to_array($testee);
        $this->assertSame($expected, $result);
    }

    public function defaultTestData()
    {
        yield 'test_1' => [
            [9, 8, 7, 6],
            [1, 2, 3, 4],
        ];
    }
}
