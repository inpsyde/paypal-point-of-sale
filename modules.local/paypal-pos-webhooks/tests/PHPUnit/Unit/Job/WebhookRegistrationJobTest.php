<?php

declare(strict_types=1);

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class WebhookRegistrationJobTest extends BrainMonkeyWpTestCase
{
    /**
     * @var list<string>
     */
    private array $calls = [];

    private function job(callable $registration, bool $canRegister = true): WebhookRegistrationJob
    {
        return new WebhookRegistrationJob(
            $registration,
            static fn (): bool => $canRegister,
            function (): void {
                $this->calls[] = 'failed';
            },
            function (): void {
                $this->calls[] = 'succeeded';
            }
        );
    }

    private function executeJob(WebhookRegistrationJob $job, ?LoggerInterface $logger = null): bool
    {
        return $job->execute(
            Mockery::mock(ContextInterface::class),
            Mockery::mock(JobRepository::class),
            $logger ?? Mockery::mock(LoggerInterface::class)
        );
    }

    public function testFailedRegistrationReportsTheFailureAndIsRetried(): void
    {
        $job = $this->job(static function (): void {
            throw new RuntimeException('Zettle is down');
        });
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with(Mockery::pattern('/Zettle is down/'));

        self::assertFalse($this->executeJob($job, $logger));
        self::assertSame(['failed'], $this->calls);
    }

    public function testSuccessfulRegistrationReportsTheSuccess(): void
    {
        $job = $this->job(static function (): void {
        });

        self::assertTrue($this->executeJob($job));
        self::assertSame(['succeeded'], $this->calls);
    }

    public function testSkipsWhenNotConnected(): void
    {
        $registered = false;
        $job = $this->job(
            static function () use (&$registered): void {
                $registered = true;
            },
            false
        );

        self::assertTrue($this->executeJob($job));
        self::assertFalse($registered);
        self::assertSame([], $this->calls);
    }
}
