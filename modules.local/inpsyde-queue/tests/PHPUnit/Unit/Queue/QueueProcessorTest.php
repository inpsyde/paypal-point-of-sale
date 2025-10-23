<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use Inpsyde\Queue\Processor\BasicQueueProcessor;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Queue\Queue\QueueWalker;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Log\LoggerInterface;

class QueueProcessorTest extends BrainMonkeyWpTestCase
{

    public function testProcess()
    {
        $repo = Mockery::mock(JobRepository::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $factory = Mockery::mock(JobRecordFactoryInterface::class);

        $walker = Mockery::mock(QueueWalker::class);
        $walker->shouldReceive('walk')->once()->andReturn(2);

        $testee = new BasicQueueProcessor($repo, $factory, $walker, $logger, 3);

        $result = $testee->process();
        $this->assertSame(2, $result);
    }
}
