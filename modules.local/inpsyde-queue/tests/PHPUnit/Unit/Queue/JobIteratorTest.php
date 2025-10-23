<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobIterator;
use Inpsyde\Queue\Queue\Job\JobRecord;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class JobIteratorTest extends BrainMonkeyWpTestCase
{

    public function testIterator()
    {
        $repo = Mockery::mock(JobRepository::class);
        $firstBatch = $this->getBatch(10);
        $repo->shouldReceive('fetch')->once()->andReturn($firstBatch);
        $lastBatch = $this->getBatch(10, true);
        $totalJobs = 10 + count($lastBatch);
        $repo->shouldReceive('fetch')->once()->andReturn($lastBatch);
        //Repo needs to return an empty array at least once so we know when to finish iteration
        $repo->shouldReceive('fetch')->once()->andReturn([]);
        $testee = new JobIterator($repo);
        $count = 0;
        foreach ($testee as $job) {
            $this->assertInstanceOf(JobRecord::class, $job);
            $count++;
        }
        $this->assertSame($totalJobs, $count);
    }

    private function getBatch(int $size, bool $last = false): array
    {
        $batch = [];
        for ($i = 0; $i < $size; $i++) {
            if ($last and (bool) random_int(0, 1) and $i) {
                break;
            }
            $batch[] = $this->createJobRecordMock();
        }

        return $batch;
    }

    private function createJobRecordMock()
    {
        $recordMock = Mockery::mock(JobRecord::class);
        $contextMock = Mockery::mock(ContextInterface::class);
        $contextMock->shouldReceive('forSite')->andReturn(1);
        $recordMock->shouldReceive('context')->andReturn($contextMock);

        return $recordMock;
    }
}
