<?php

declare(strict_types=1);

use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRecord;
use Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class RetryDelayTest extends BrainMonkeyWpTestCase
{
    private function delay(string $type, int $retryCount): int
    {
        $extensions = require dirname(__DIR__, 4) . '/extensions.php';
        $delay = $extensions['inpsyde.queue.failed.retry.delay'](static fn (): int => 0);

        $job = Mockery::mock(Job::class);
        $job->shouldReceive('type')->andReturn($type);
        $record = Mockery::mock(JobRecord::class);
        $record->shouldReceive('job')->andReturn($job);
        $record->shouldReceive('context')->andReturn(new Context(new stdClass(), new DateTime(), 1, $retryCount));

        return $delay($record);
    }

    /**
     * @dataProvider apiJobs
     */
    public function testApiJobsBackOff(string $type): void
    {
        self::assertSame([120, 480, 1920], [$this->delay($type, 0), $this->delay($type, 1), $this->delay($type, 2)]);
    }

    public function apiJobs(): array
    {
        return [
            'webhook registration' => [WebhookRegistrationJob::TYPE],
        ];
    }

    public function testOtherJobsKeepTheQueueDefault(): void
    {
        self::assertSame(0, $this->delay('export-product', 1));
    }
}
