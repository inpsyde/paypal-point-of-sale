<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use Inpsyde\Queue\Processor\BasicQueueProcessor;
use Inpsyde\Queue\Queue\Job\BasicJobRecord;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRecord;
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

    /**
     * @dataProvider retryDelayProvider
     */
    public function testRetriesFailedJobsAfterTheConfiguredDelay(?callable $delay, ?int $expectedDelay): void
    {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('type')->andReturn('flaky');
        $job->shouldReceive('execute')->once()->andReturn(false);
        $failed = new BasicJobRecord($job, Context::fromArray(['id' => 1], 1, 1, null, 7));
        $retries = [];
        $repo = Mockery::mock(JobRepository::class);
        $repo->shouldReceive('delete')->once()->with($failed);
        $repo->shouldReceive('add')->once()->andReturnUsing(static function (JobRecord $retry) use (&$retries): bool {
            $retries[] = $retry;

            return true;
        });
        $factory = Mockery::mock(JobRecordFactoryInterface::class);
        $factory->shouldReceive('fromData')->andReturnUsing(
            static fn (string $type, ContextInterface $context): JobRecord => new BasicJobRecord($job, $context)
        );
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $testee = new BasicQueueProcessor($repo, $factory, Mockery::mock(QueueWalker::class), $logger, 3, null, $delay);
        $testee->processSingleJob($failed);

        $context = $retries[0]->context();
        $this->assertSame(2, $context->retryCount());
        $this->assertSame(['id' => 1], (array) $context->args());
        if ($expectedDelay === null) {
            $this->assertNull($context->notBefore());

            return;
        }
        $this->assertEqualsWithDelta(time() + $expectedDelay, $context->notBefore()->getTimestamp(), 2);
    }

    public function retryDelayProvider(): array
    {
        return [
            'no delay configured' => [null, null],
            'zero delay' => [static fn (): int => 0, null],
            'delay from the failed record' => [
                static fn (JobRecord $record): int => 60 * ($record->context()->retryCount() + 1),
                120,
            ],
        ];
    }
}
