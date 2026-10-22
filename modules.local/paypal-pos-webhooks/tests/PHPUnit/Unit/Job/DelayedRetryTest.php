<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Job\DelayedRetry;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\expect;

class DelayedRetryTest extends BrainMonkeyWpTestCase
{
    public function testBacksOffExponentiallyAndCountsAttempts(): void
    {
        expect('wp_schedule_single_event')
            ->once()
            ->with(Mockery::on(static fn (int $at) => abs($at - (time() + 240)) <= 2), DelayedRetry::HOOK, ['job', ['id' => 1, 'attempt' => 3]]);

        self::assertSame(240, (new DelayedRetry(static function (): void {
        }))->retry('job', ['id' => 1, 'attempt' => 2]));
    }

    public function testGivesUpAfterTheLastAttempt(): void
    {
        expect('wp_schedule_single_event')->never();

        self::assertNull((new DelayedRetry(static function (): void {
        }))->retry('job', ['attempt' => DelayedRetry::MAX_ATTEMPTS]));
    }

    public function testTheCronEventQueuesTheJob(): void
    {
        $queued = [];
        (new DelayedRetry(static function (string $type, array $args) use (&$queued): void {
            $queued = [$type, $args];
        }))->enqueue('job', ['attempt' => 1]);

        self::assertSame(['job', ['attempt' => 1]], $queued);
    }
}
