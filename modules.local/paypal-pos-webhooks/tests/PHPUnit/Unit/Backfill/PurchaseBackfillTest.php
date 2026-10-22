<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\Webhooks\Job\BackfillJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\expect;
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

    protected function setUp(): void
    {
        parent::setUp();
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
        bool $connected = true,
        bool $autoRecovery = true,
        bool $importOnSetup = true
    ): PurchaseBackfill {

        return new PurchaseBackfill(
            function (string $type, array $args): void {
                $this->enqueued[] = [$type, $args];
            },
            $enabled,
            static fn (): bool => $connected,
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
        $this->options[PurchaseBackfill::DEACTIVATED_AT_OPTION] = strtotime('2026-10-20T17:00:00+00:00');

        self::assertTrue($this->backfill()->recoverAfterReactivation());
        self::assertSame('2026-10-20T16:00:00+00:00', $this->enqueued[0][1]['startDate']);
        self::assertArrayNotHasKey(PurchaseBackfill::DEACTIVATED_AT_OPTION, $this->options);
    }

    public function testReactivationRecoveryReplacesARunningImport(): void
    {
        $backfill = $this->backfill();
        $backfill->start();
        $this->options[PurchaseBackfill::DEACTIVATED_AT_OPTION] = time();

        self::assertTrue($backfill->recoverAfterReactivation());
        self::assertCount(2, $this->enqueued);
        self::assertSame($this->enqueued[1][1]['run'], $backfill->status()['run']);
    }

    public function testRecoveryCanBeSwitchedOff(): void
    {
        $this->options[PurchaseBackfill::DEACTIVATED_AT_OPTION] = time();

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

    public function testFirstActivationImportsNothing(): void
    {
        self::assertFalse($this->backfill()->recoverAfterReactivation());
        self::assertSame([], $this->enqueued);
    }

    public function testReactivationWithoutAConnectedAccountForgetsTheGap(): void
    {
        $this->options[PurchaseBackfill::DEACTIVATED_AT_OPTION] = time();

        self::assertFalse($this->backfill(true, false)->recoverAfterReactivation());
        self::assertSame([], $this->enqueued);
        self::assertArrayNotHasKey(PurchaseBackfill::DEACTIVATED_AT_OPTION, $this->options);
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
