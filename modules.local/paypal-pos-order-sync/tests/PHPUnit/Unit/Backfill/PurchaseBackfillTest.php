<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Container\WritableContainerInterface;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\OrderSync\Job\BackfillJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\stubTranslationFunctions;
use function Brain\Monkey\Functions\when;

class PurchaseBackfillTest extends BrainMonkeyWpTestCase
{
    /**
     * @var array<int, array{0: string, 1: array<string, mixed>}>
     */
    private array $enqueued = [];

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * @var array<string, mixed>
     */
    private array $setupInfoValues = [];

    private WritableContainerInterface $setupInfo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupInfo = Mockery::mock(WritableContainerInterface::class);
        $this->setupInfo->shouldReceive('has')->andReturnUsing(fn (string $key): bool => isset($this->setupInfoValues[$key]));
        $this->setupInfo->shouldReceive('get')->andReturnUsing(fn (string $key) => $this->setupInfoValues[$key]);
        $this->setupInfo->shouldReceive('set')->andReturnUsing(function (string $key, $value): void {
            $this->setupInfoValues[$key] = $value;
        });
        $this->setupInfo->shouldReceive('unset')->andReturnUsing(function (string $key): void {
            unset($this->setupInfoValues[$key]);
        });
        stubTranslationFunctions();
        when('get_option')->alias(fn (string $name, $default = false) => $this->options[$name] ?? $default);
        when('update_option')->alias(function (string $name, $value): bool {
            $this->options[$name] = $value;

            return true;
        });
        when('delete_option')->alias(function (string $name): bool {
            unset($this->options[$name]);

            return true;
        });
    }

    private function backfill(
        bool $enabled = true,
        bool $onboarded = true,
        bool $autoRecovery = true,
        bool $importOnSetup = true
    ): PurchaseBackfill {

        return new PurchaseBackfill(
            function (string $type, array $args): void {
                $this->enqueued[] = [$type, $args];
            },
            $enabled,
            static fn (): bool => $onboarded,
            $this->setupInfo,
            $autoRecovery,
            $importOnSetup
        );
    }

    public function testStartQueuesTheFirstPageOfANewRun(): void
    {
        self::assertTrue($this->backfill()->start());

        [$type, $args] = $this->enqueued[0];
        self::assertSame(BackfillJob::TYPE, $type);
        self::assertSame(['startDate' => '', 'cursor' => '', 'page' => 0], array_diff_key($args, ['run' => 0]));
        self::assertSame($args['run'], $this->backfill()->status()['run']);
    }

    public function testStartDoesNothingWhenOrderSyncIsDisabled(): void
    {
        self::assertFalse($this->backfill(false)->start());
        self::assertSame([], $this->enqueued);
    }

    public function testDoesNotStartASecondRunWhileOneIsRunning(): void
    {
        $backfill = $this->backfill();
        $backfill->start();

        self::assertFalse($backfill->start());
        self::assertCount(1, $this->enqueued);
    }

    public function testMissedSyncCoversTheLast30Days(): void
    {
        $this->backfill()->startMissed();

        $since = new DateTimeImmutable($this->enqueued[0][1]['startDate']);
        self::assertEqualsWithDelta(time() - 30 * 86400, $since->getTimestamp(), 5);
    }

    public function testReactivationImportsFromAnHourBeforeDeactivation(): void
    {
        $this->setupInfo->set(PurchaseBackfill::DEACTIVATED_AT_KEY, strtotime('2026-10-20T17:00:00+00:00'));

        self::assertTrue($this->backfill()->recoverAfterReactivation());
        self::assertSame('2026-10-20T16:00:00+00:00', $this->enqueued[0][1]['startDate']);
        self::assertFalse($this->setupInfo->has(PurchaseBackfill::DEACTIVATED_AT_KEY));
    }

    public function testReactivationRecoveryReplacesARunningImport(): void
    {
        $backfill = $this->backfill();
        $backfill->start();
        $this->setupInfo->set(PurchaseBackfill::DEACTIVATED_AT_KEY, time());

        $replaced = $backfill->status()['run'];

        self::assertTrue($backfill->recoverAfterReactivation());
        self::assertCount(2, $this->enqueued);
        self::assertSame($this->enqueued[1][1]['run'], $backfill->status()['run']);
        self::assertFalse($backfill->isActive($replaced));
    }

    public function testRecoveryCanBeSwitchedOff(): void
    {
        $this->setupInfo->set(PurchaseBackfill::DEACTIVATED_AT_KEY, time());

        self::assertFalse($this->backfill(true, true, false)->recoverAfterReactivation());
        self::assertSame([], $this->enqueued);
    }

    public function testSetupImportRunsOnceOverTheWholeHistory(): void
    {
        $backfill = $this->backfill();

        self::assertTrue($backfill->startSetupImport());
        self::assertSame('', $this->enqueued[0][1]['startDate']);

        $backfill->finish($backfill->status()['run']);
        self::assertFalse($backfill->startSetupImport());
        self::assertCount(1, $this->enqueued);
    }

    public function testSetupImportCanBeSwitchedOff(): void
    {
        self::assertFalse($this->backfill(true, true, true, false)->startSetupImport());
        self::assertSame([], $this->enqueued);
    }

    public function testSetupImportReplacesARunningImport(): void
    {
        $backfill = $this->backfill();
        $backfill->start(new DateTimeImmutable('-30 days'));

        self::assertTrue($backfill->startSetupImport());
        self::assertSame('', $this->enqueued[1][1]['startDate']);
        self::assertSame($this->enqueued[1][1]['run'], $backfill->status()['run']);
    }

    public function testSetupImportStaysPendingWhileOrderSyncIsDisabled(): void
    {
        self::assertFalse($this->backfill(false)->startSetupImport());
        self::assertFalse($this->setupInfo->has(PurchaseBackfill::SETUP_IMPORT_KEY));
    }

    public function testWebhookGapIsImportedFromAnHourBeforeTheFirstFailure(): void
    {
        $firstFailure = time() - 600;
        $this->options[PurchaseBackfill::WEBHOOK_GAP_OPTION] = $firstFailure;
        $backfill = $this->backfill();
        $backfill->rememberWebhookGap();

        self::assertTrue($backfill->recoverWebhookGap());
        $since = new DateTimeImmutable($this->enqueued[0][1]['startDate']);
        self::assertSame($firstFailure - 3600, $since->getTimestamp());
        self::assertFalse($backfill->recoverWebhookGap());
    }

    public function testWebhookGapRestartsARunningImportThatCoversIt(): void
    {
        $backfill = $this->backfill();
        $backfill->start();
        $backfill->rememberWebhookGap();

        self::assertTrue($backfill->recoverWebhookGap());
        self::assertSame('', $this->enqueued[1][1]['startDate']);
        self::assertArrayNotHasKey(PurchaseBackfill::WEBHOOK_GAP_OPTION, $this->options);
    }

    /**
     * @dataProvider importResults
     */
    public function testAnImportFinishingLeavesTheGapUntilRegistrationWorks(string $error): void
    {
        $backfill = $this->backfill();
        $backfill->startSetupImport();
        $backfill->rememberWebhookGap();

        $backfill->finish($backfill->status()['run'], $error);

        self::assertArrayHasKey(PurchaseBackfill::WEBHOOK_GAP_OPTION, $this->options);
        self::assertCount(1, $this->enqueued);

        self::assertTrue($backfill->recoverWebhookGap());
        self::assertNotSame('', $this->enqueued[1][1]['startDate']);
    }

    public function importResults(): array
    {
        return ['succeeded' => [''], 'failed' => ['Malformed purchase']];
    }

    public function testWebhookGapReplacesARunningImportThatStartedAfterIt(): void
    {
        $firstFailure = time() - 7200;
        $this->options[PurchaseBackfill::WEBHOOK_GAP_OPTION] = $firstFailure;
        $backfill = $this->backfill();
        $backfill->start(new DateTimeImmutable('-30 minutes'));
        $replaced = $backfill->status()['run'];

        self::assertTrue($backfill->recoverWebhookGap());
        self::assertFalse($backfill->isActive($replaced));
        $since = new DateTimeImmutable($this->enqueued[1][1]['startDate']);
        self::assertSame($firstFailure - 3600, $since->getTimestamp());
    }

    public function testWebhookGapIsIgnoredUntilOnboardingIsCompleted(): void
    {
        $this->backfill(true, false)->rememberWebhookGap();
        self::assertArrayNotHasKey(PurchaseBackfill::WEBHOOK_GAP_OPTION, $this->options);

        $this->options[PurchaseBackfill::WEBHOOK_GAP_OPTION] = time();
        self::assertFalse($this->backfill(true, false)->recoverWebhookGap());
        self::assertArrayHasKey(PurchaseBackfill::WEBHOOK_GAP_OPTION, $this->options);
        self::assertSame([], $this->enqueued);
    }

    public function testReactivationResumesAnInterruptedSetupImport(): void
    {
        $backfill = $this->backfill();
        $backfill->startSetupImport();
        $backfill->rememberDeactivation();

        self::assertTrue($backfill->recoverAfterReactivation());
        self::assertSame('', $this->enqueued[1][1]['startDate']);
    }

    public function testAFinishedImportsRangeIsNotTakenOver(): void
    {
        $backfill = $this->backfill();
        $backfill->start();
        $backfill->finish($backfill->status()['run']);

        $backfill->startMissed();

        self::assertNotSame('', $this->enqueued[1][1]['startDate']);
    }

    public function testAFailedImportsRangeIsNotTakenOverAsItWouldFailAgain(): void
    {
        $backfill = $this->backfill();
        $backfill->start();
        $backfill->finish($backfill->status()['run'], 'Malformed purchase');

        $backfill->startMissed();

        self::assertNotSame('', $this->enqueued[1][1]['startDate']);
    }

    public function testDeactivationStopsARunningImport(): void
    {
        $backfill = $this->backfill();
        $backfill->start();

        $run = $backfill->status()['run'];

        $backfill->rememberDeactivation();

        self::assertSame('interrupted', $backfill->status()['state']);
        self::assertFalse($backfill->isRunning());
        self::assertFalse($backfill->isActive($run));
    }

    public function testFirstActivationImportsNothing(): void
    {
        self::assertFalse($this->backfill()->recoverAfterReactivation());
        self::assertSame([], $this->enqueued);
    }

    public function testReactivationBeforeOnboardingIsCompletedForgetsTheGap(): void
    {
        $this->setupInfo->set(PurchaseBackfill::DEACTIVATED_AT_KEY, time());

        self::assertFalse($this->backfill(true, false)->recoverAfterReactivation());
        self::assertSame([], $this->enqueued);
        self::assertFalse($this->setupInfo->has(PurchaseBackfill::DEACTIVATED_AT_KEY));
    }

    public function testASuccessfulRetryClearsTheErrorAndOtherRunsAreIgnored(): void
    {
        $backfill = $this->backfill();
        $backfill->start();
        $run = $backfill->status()['run'];

        $backfill->finish($run, '429 Too Many Requests');
        $backfill->recordPage($run, 3);
        $backfill->recordPage('older-run', 50);
        $backfill->finish('older-run');

        self::assertSame(['state' => 'running', 'queued' => 3, 'pages' => 1, 'error' => ''], array_intersect_key(
            $backfill->status(),
            ['state' => 0, 'queued' => 0, 'pages' => 0, 'error' => 0]
        ));
    }
}
