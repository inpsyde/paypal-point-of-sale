<?php

declare(strict_types=1);

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class WebhookRegistrationJobTest extends BrainMonkeyWpTestCase
{
    private function executeJob(WebhookRegistrationJob $job, LoggerInterface $logger): bool
    {
        return $job->execute(Mockery::mock(ContextInterface::class), Mockery::mock(JobRepository::class), $logger);
    }

    public function testFailedRegistrationIsRetried(): void
    {
        $job = new WebhookRegistrationJob(
            static function (): void {
                throw new RuntimeException('Zettle is down');
            },
            static fn (): bool => true
        );
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with(Mockery::pattern('/Zettle is down/'));

        self::assertFalse($this->executeJob($job, $logger));
    }

    public function testSkipsWhenNotConnected(): void
    {
        $registered = false;
        $job = new WebhookRegistrationJob(
            static function () use (&$registered): void {
                $registered = true;
            },
            static fn (): bool => false
        );

        self::assertTrue($this->executeJob($job, Mockery::mock(LoggerInterface::class)));
        self::assertFalse($registered);
    }
}
