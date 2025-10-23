<?php
declare(strict_types=1);

namespace Inpsyde\WcEvents\Tests\Unit;

use Inpsyde\WcEvents\Event\ProductChangeEvent;
use Inpsyde\WcEvents\Event\ProductEventListenerRegistry;
use Inpsyde\WcEvents\ParameterDeriver;
use Mockery;
use Mockery\MockInterface;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use WC_Product;
use WC_Product_External;
use WC_Product_Grouped;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class ProductEventListenerRegistryTest extends BrainMonkeyWpTestCase
{

    /**
     * @param $oldProduct
     * @param $newProduct
     * @param $expectCalled
     *
     * @dataProvider defaultTestData
     */
    public function testOnChange(
        $oldProduct,
        $newProduct,
        array $callbackFactories,
        $expectCalled
    ) {
        $called = 0;
        /**
         * Yeah yeah, ParameterDeriver should be a mock. But how would you mock
         * something like this whithout essentially duplicating it here??
         */
        $testee = new ProductEventListenerRegistry(new ParameterDeriver());
        $spy = function () use (&$called) {
            $called++;
        };
        $callbacks = array_map(
            function (callable $factory) use ($spy) : callable {
                return $factory($spy);
            },
            $callbackFactories
        );
        $testee->onChange(...$callbacks);
        $event = Mockery::mock(ProductChangeEvent::class);
        $event->shouldReceive('new')->andReturn($newProduct);
        $event->shouldReceive('old')->andReturn($oldProduct);
        $listeners = $testee->getListenersForEvent($event);
        foreach ($listeners as $listener) {
            $listener($event);
        }
        $this->assertSame(
            $expectCalled,
            $called,
            "Dummy listener was only called {$called}/{$expectCalled} times"
        );
    }

    private function mock(string $className): MockInterface
    {
        $productTypeMap = [
            WC_Product_Simple::class => [WC_Product::class],
            WC_Product_Variable::class => [WC_Product::class],
            WC_Product_Variation::class => [WC_Product_Simple::class, WC_Product::class],
            WC_Product_Grouped::class => [WC_Product::class],
            WC_Product_External::class => [WC_Product::class],
        ];
        $types = $productTypeMap[$className];
        array_unshift($types, $className);

        return Mockery::mock(implode(', ', $types));
    }

    public function defaultTestData()
    {
        yield 'happy path' => [
            $this->mock(WC_Product_Simple::class),
            $this->mock(WC_Product_Simple::class),
            [
                function (callable $dummy): callable {
                    return function (WC_Product $new) use ($dummy) {
                        $dummy();
                    };
                },
            ],
            1,
        ];

        yield 'mismatching listener type' => [
            $this->mock(WC_Product_Simple::class),
            $this->mock(WC_Product_Simple::class),
            [
                function (callable $dummy): callable {
                    return function (WC_Product_Variation $new) use ($dummy) {
                        $dummy();
                    };
                },
            ],
            0,
        ];

        yield 'multiple callbacks with one mismatch ' => [
            $this->mock(WC_Product_Simple::class),
            $this->mock(WC_Product_Simple::class),
            [
                function (callable $dummy): callable {
                    return function (WC_Product $new) use ($dummy) {
                        $dummy();
                    };
                },
                function (callable $dummy): callable {
                    return function (WC_Product_Simple $new) use ($dummy) {
                        $dummy();
                    };
                },
                function (callable $dummy): callable {
                    return function (WC_Product_Variation $new) use ($dummy) {
                        $dummy();
                    };
                },
            ],
            2,
        ];
    }
}