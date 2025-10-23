<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Iterator\WcProductIterator;
use Syde\PayPal\PointOfSale\PhpSdk\Iterator\WcProductIteratorAggregate;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class WcProductIteratorAggregateTest extends BrainMonkeyWpTestCase
{

    /**
     * @dataProvider defaultTestData
     *
     * @param array $expected
     * @param WcProductIterator ...$iterators
     */
    public function testIteratorWithoutWcProduct(array $expected, WcProductIterator ...$iterators)
    {
        $testee = new WcProductIteratorAggregate(...$iterators);
        $result = iterator_to_array($testee);
        $this->assertEmpty($result);
    }

    /**
     * @dataProvider defaultTestData
     *
     * @param array $expected
     * @param WcProductIterator ...$iterators
     */
    public function testIteratorWithProduct(array $expected, WcProductIterator ...$iterators)
    {
        $wcProduct = Mockery::mock(WC_Product::class);
        $testee = new WcProductIteratorAggregate(...$iterators);
        $testee->switchProduct($wcProduct);
        $result = iterator_to_array($testee);
        $this->assertSame($expected, $result);
    }

    public function defaultTestData()
    {
        yield 'Happy Path' => [
            [1, 2, 3, 4, 5, 6],
            $this->createWcProductIterator([1, 2, 3]),
            $this->createWcProductIterator([4, 5, 6]),
        ];

        yield 'With gaps' => [
            [1, 2, 3, 4, 5, 6],
            $this->createWcProductIterator([1, 2, 3]),
            $this->createWcProductIterator([]),
            $this->createWcProductIterator([4, 5, 6]),
        ];
    }

    private function createWcProductIterator(array $data)
    {
        return new class($data) extends ArrayIterator implements WcProductIterator {

            public function switchProduct(WC_Product $product): void
            {
                // Silence
            }
        };
    }
}
