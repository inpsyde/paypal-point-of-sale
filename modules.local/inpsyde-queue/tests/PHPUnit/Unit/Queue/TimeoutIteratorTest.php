<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use Inpsyde\Queue\Queue\StoppableQueueWalker;
use Inpsyde\Queue\Queue\TimeStopper;
use Iterator;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class TimeoutIteratorTest extends BrainMonkeyWpTestCase
{
    public function testIterateStopsAsSoonAsTimerStops()
    {
        $runs = 5;
        /**
         * Use a stub because I can't be assed to implement expecations for a mock.
         */
        $iterator = new class implements Iterator
        {

            public function current()
            {
            }

            public function next()
            {
            }

            public function key()
            {
            }

            public function valid()
            {
                return true;
            }

            public function rewind()
            {
            }
        };

        $timer = Mockery::mock(TimeStopper::class);
        $timer->shouldReceive('start');
        $timer->shouldReceive('isStopped')->times($runs - 1)->andReturn(false);
        $timer->shouldReceive('isStopped')->once()->andReturn(true);

        $calls = 0;
        $callback = function () use (&$calls) {
            $calls++;
        };
        $testee = new StoppableQueueWalker($iterator, $timer);
        $testee->walk($callback);
        $this->assertSame($calls, $runs);
    }
}
