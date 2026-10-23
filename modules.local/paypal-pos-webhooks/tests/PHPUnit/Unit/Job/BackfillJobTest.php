<?php

declare(strict_types=1);

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Purchases\Purchases;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseHistoryFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\UserFactory;
use Syde\PayPal\PointOfSale\Webhooks\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\Webhooks\Job\BackfillJob;
use Syde\PayPal\PointOfSale\Webhooks\Job\CreateOrderJob;
use Syde\PayPal\PointOfSale\Webhooks\Job\DelayedRetry;
use Syde\PayPal\PointOfSale\Webhooks\Order\PaymentLabels;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseFilter;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseRefundFactory;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class BackfillJobTest extends BrainMonkeyWpTestCase
{
    /**
     * @var array<int, array{0: string, 1: array<string, mixed>}>
     */
    private array $enqueued = [];

    private function purchase(string $uuid, bool $refund = false, string $paymentType = 'IZETTLE_CASH'): Purchase
    {
        return (new PurchaseFactory())->create(
            $uuid,
            new DateTime(),
            'GB',
            'GBP',
            (new UserFactory())->create('1', 'Cashier'),
            1,
            1,
            840.0,
            140.0,
            [],
            [['type' => $paymentType, 'amount' => 840]],
            [],
            false,
            $refund,
            false,
            null,
            null,
            null,
            "{$uuid}-dashed"
        );
    }

    /**
     * @param Purchase[] $purchases
     */
    private function page(array $purchases, string $nextCursor): object
    {
        return (new PurchaseHistoryFactory())->create('', $nextCursor, new PurchaseCollection($purchases));
    }

    private function fullPage(string $nextCursor): object
    {
        return $this->page(array_map(fn (int $i) => $this->purchase("p{$i}"), range(1, BackfillJob::PAGE_SIZE)), $nextCursor);
    }

    private function backfill(bool $enabled = true): PurchaseBackfill
    {
        $backfill = Mockery::mock(PurchaseBackfill::class);
        $backfill->shouldReceive('isEnabled')->andReturn($enabled);
        $backfill->shouldReceive('isActive')->andReturnTrue();
        $backfill->shouldIgnoreMissing();

        return $backfill;
    }

    /**
     * @param string[] $paymentTypes
     */
    private function job(
        Purchases $purchases,
        PurchaseBackfill $backfill,
        ?DelayedRetry $retry = null,
        ?PurchaseOrderFactory $orders = null,
        array $paymentTypes = [],
        ?PurchaseRefundFactory $refunds = null
    ): BackfillJob {

        if ($orders === null) {
            $orders = Mockery::mock(PurchaseOrderFactory::class);
            $orders->shouldReceive('orderExists')->andReturnFalse();
        }
        if ($refunds === null) {
            $refunds = Mockery::mock(PurchaseRefundFactory::class);
            $refunds->shouldReceive('isRecorded')->andReturnFalse();
        }

        return new BackfillJob(
            $purchases,
            $orders,
            $refunds,
            $backfill,
            new PurchaseFilter(new PaymentLabels(), $paymentTypes),
            $retry ?? Mockery::mock(DelayedRetry::class)->shouldIgnoreMissing(),
            function (string $type, array $args): void {
                $this->enqueued[] = [$type, $args];
            }
        );
    }

    private function executeJob(BackfillJob $job, array $args = ['run' => 'run-1'], ?LoggerInterface $logger = null): bool
    {
        $context = Mockery::mock(ContextInterface::class);
        $context->shouldReceive('args')->andReturn((object) $args);

        return $job->execute($context, Mockery::mock(JobRepository::class), $logger ?? Mockery::mock(LoggerInterface::class));
    }

    public function testFinishesTheRunWhenOrderSyncWasDisabled(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldNotReceive('list');
        $backfill = $this->backfill(false);
        $backfill->shouldReceive('finish')->once()->with('run-1');

        self::assertTrue($this->executeJob($this->job($purchases, $backfill)));
    }

    public function testQueuesMissingSalesAndRefundsByTheWebhookUuid(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('list')->once()->andReturn($this->page(
            [
                $this->purchase('new'),
                $this->purchase('synced'),
                $this->purchase('refund', true),
                $this->purchase('recorded-refund', true),
                $this->purchase('card', false, 'IZETTLE_CARD'),
            ],
            ''
        ));
        $orders = Mockery::mock(PurchaseOrderFactory::class);
        $orders->shouldReceive('orderExists')->with('new')->andReturnFalse();
        $orders->shouldReceive('orderExists')->with('synced')->andReturnTrue();
        $refunds = Mockery::mock(PurchaseRefundFactory::class);
        $refunds->shouldReceive('isRecorded')->andReturnUsing(static fn ($refund) => $refund->uuid() === 'recorded-refund');
        $backfill = $this->backfill();
        $backfill->shouldReceive('recordPage')->once()->with('run-1', 2);
        $backfill->shouldReceive('finish')->once()->with('run-1');

        $this->executeJob($this->job($purchases, $backfill, null, $orders, ['IZETTLE_CASH'], $refunds));

        self::assertSame(
            [
                [CreateOrderJob::TYPE, ['purchaseUuid' => 'new-dashed']],
                [CreateOrderJob::TYPE, ['purchaseUuid' => 'refund-dashed']],
            ],
            $this->enqueued
        );
    }

    public function testQueuesTheNextPage(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('list')
            ->once()
            ->with('HASH1', BackfillJob::PAGE_SIZE, Mockery::type(DateTimeImmutable::class))
            ->andReturn($this->fullPage('HASH2'));
        $backfill = $this->backfill();
        $backfill->shouldNotReceive('finish');

        $this->executeJob(
            $this->job($purchases, $backfill),
            ['run' => 'run-1', 'cursor' => 'HASH1', 'startDate' => '2026-10-20T10:00:00+00:00', 'page' => 3]
        );

        self::assertSame(
            [
                BackfillJob::TYPE,
                ['run' => 'run-1', 'startDate' => '2026-10-20T10:00:00+00:00', 'cursor' => 'HASH2', 'page' => 4],
            ],
            end($this->enqueued)
        );
    }

    public function testAReplacedOrStoppedRunStops(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldNotReceive('list');
        $backfill = Mockery::mock(PurchaseBackfill::class);
        $backfill->shouldReceive('isActive')->with('old-run')->andReturnFalse();
        $backfill->shouldNotReceive('finish');

        self::assertTrue($this->executeJob($this->job($purchases, $backfill), ['run' => 'old-run']));
    }

    public function testStopsWhenTheCursorDoesNotAdvance(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('list')->once()->andReturn($this->fullPage('HASH1'));
        $backfill = $this->backfill();
        $backfill->shouldReceive('finish')->once()->with('run-1');

        $this->executeJob($this->job($purchases, $backfill), ['run' => 'run-1', 'cursor' => 'HASH1']);

        self::assertNotContains(BackfillJob::TYPE, array_column($this->enqueued, 0));
    }

    public function testBacksOffAndKeepsTheRunGoingOnAnApiError(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('list')->andThrow(new RuntimeException('429 Too Many Requests'));
        $retry = Mockery::mock(DelayedRetry::class);
        $retry->shouldReceive('retry')->once()->with(BackfillJob::TYPE, ['run' => 'run-1'])->andReturn(60);
        $backfill = $this->backfill();
        $backfill->shouldReceive('recordError')->once()->with('run-1', '429 Too Many Requests');
        $backfill->shouldNotReceive('finish');
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        self::assertTrue($this->executeJob($this->job($purchases, $backfill, $retry), ['run' => 'run-1'], $logger));
    }

    public function testFailsTheRunWhenNoRetriesAreLeft(): void
    {
        $purchases = Mockery::mock(Purchases::class);
        $purchases->shouldReceive('list')->andReturn($this->page([$this->purchase('new')], ''));
        $orders = Mockery::mock(PurchaseOrderFactory::class);
        $orders->shouldReceive('orderExists')->andThrow(new RuntimeException('DB gone'));
        $retry = Mockery::mock(DelayedRetry::class);
        $retry->shouldReceive('retry')->once()->andReturnNull();
        $backfill = $this->backfill();
        $backfill->shouldReceive('finish')->once()->with('run-1', 'DB gone');
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $this->executeJob($this->job($purchases, $backfill, $retry, $orders), ['run' => 'run-1'], $logger);
    }
}
