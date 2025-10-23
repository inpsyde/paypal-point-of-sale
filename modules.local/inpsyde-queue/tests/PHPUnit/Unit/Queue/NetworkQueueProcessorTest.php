<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use Inpsyde\Queue\NetworkState;
use Inpsyde\Queue\Processor\BasicQueueProcessor;
use Inpsyde\Queue\Processor\NetworkQueueProcessor;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class NetworkQueueProcessorTest extends BrainMonkeyWpTestCase
{

    public function testHandlesNetworkState()
    {
        $networkState = Mockery::mock(NetworkState::class);
        $networkState->shouldReceive('restore')->once();

        $networkStateFactory = function () use ($networkState): NetworkState {
            return $networkState;
        };

        $child = Mockery::mock(BasicQueueProcessor::class);
        $expectedResult = rand(0, PHP_INT_MAX);
        $child->shouldReceive('process')->once()->andReturn($expectedResult);
        $testee = new NetworkQueueProcessor($child, $networkStateFactory);

        $result = $testee->process();
        $this->assertSame($expectedResult, $result);
    }
}
