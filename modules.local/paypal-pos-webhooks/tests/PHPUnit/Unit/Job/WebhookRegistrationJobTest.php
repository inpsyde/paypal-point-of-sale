<?php

declare(strict_types=1);

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\Webhooks\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class WebhookRegistrationJobTest extends BrainMonkeyWpTestCase
{
    private function executeJob(WebhookRegistrationJob $job, ?LoggerInterface $logger = null): bool
    {
        return $job->execute(
            Mockery::mock(ContextInterface::class),
            Mockery::mock(JobRepository::class),
            $logger ?? Mockery::mock(LoggerInterface::class)
        );
    }

    public function testFailedRegistrationRemembersTheGapAndIsRetried(): void
    {
        $backfill = Mockery::mock(PurchaseBackfill::class);
        $backfill->shouldReceive('rememberWebhookGap')->once();
        $backfill->shouldNotReceive('recoverWebhookGap');
        $job = new WebhookRegistrationJob(
            static function (): void {
                throw new RuntimeException('Zettle is down');
            },
            static fn (): bool => true,
            $backfill
        );
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with(Mockery::pattern('/Zettle is down/'));

        self::assertFalse($this->executeJob($job, $logger));
    }

    public function testSuccessfulRegistrationRecoversTheGap(): void
    {
        $backfill = Mockery::mock(PurchaseBackfill::class);
        $backfill->shouldReceive('recoverWebhookGap')->once();
        $job = new WebhookRegistrationJob(static function (): void {
        }, static fn (): bool => true, $backfill);

        self::assertTrue($this->executeJob($job));
    }

    public function testSkipsWhenNotConnected(): void
    {
        $registered = false;
        $backfill = Mockery::mock(PurchaseBackfill::class);
        $backfill->shouldNotReceive('recoverWebhookGap');
        $job = new WebhookRegistrationJob(
            static function () use (&$registered): void {
                $registered = true;
            },
            static fn (): bool => false,
            $backfill
        );

        self::assertTrue($this->executeJob($job));
        self::assertFalse($registered);
    }
}
