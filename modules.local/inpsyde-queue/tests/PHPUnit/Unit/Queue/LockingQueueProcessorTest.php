<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use Inpsyde\Queue\Exception\QueueLockedException;
use Inpsyde\Queue\Processor\BasicQueueProcessor;
use Inpsyde\Queue\Processor\LockingQueueProcessor;
use Inpsyde\Queue\Queue\Locker;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class LockingQueueProcessorTest extends BrainMonkeyWpTestCase
{

    public function testWithLockedQueue()
    {
        $locker = Mockery::mock(Locker::class);
        $locker->shouldReceive('isLocked')->once()->andReturn(true);

        $child = Mockery::mock(BasicQueueProcessor::class);
        $child->shouldNotReceive('process');
        $testee = new LockingQueueProcessor($child, $locker);

        $this->expectException(QueueLockedException::class);

        $testee->process();
    }

    public function testProcess()
    {
        $locker = Mockery::mock(Locker::class);
        $locker->shouldReceive('isLocked')->once()->andReturn(false);
        $locker->shouldReceive('lock')->once()->andReturn(true);
        $locker->shouldReceive('unlock')->once()->andReturn(true);

        $expectedResult = rand(0, PHP_INT_MAX);
        $child = Mockery::mock(BasicQueueProcessor::class);
        $child->shouldReceive('process')->once()->andReturn($expectedResult);
        $testee = new LockingQueueProcessor($child, $locker);

        $result = $testee->process();
        $this->assertSame($expectedResult, $result);
    }
}
