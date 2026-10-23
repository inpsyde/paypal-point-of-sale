<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job;

use DateTimeImmutable;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContextInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Purchases\Purchases;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Backfill\PurchaseBackfill;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseFilter;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseRefundFactory;
use Throwable;
/**
 * Imports one page of Zettle purchase history, then queues itself for the next page.
 */
class BackfillJob implements Job
{
    public const TYPE = 'purchase-backfill';
    public const PAGE_SIZE = 100;
    private Purchases $purchases;
    private PurchaseOrderFactory $orderFactory;
    private PurchaseRefundFactory $refundFactory;
    private PurchaseBackfill $backfill;
    private PurchaseFilter $filter;
    private DelayedRetry $retry;
    /**
     * @var callable
     */
    private $enqueueJob;
    public function __construct(Purchases $purchases, PurchaseOrderFactory $orderFactory, PurchaseRefundFactory $refundFactory, PurchaseBackfill $backfill, PurchaseFilter $filter, DelayedRetry $retry, callable $enqueueJob)
    {
        $this->purchases = $purchases;
        $this->orderFactory = $orderFactory;
        $this->refundFactory = $refundFactory;
        $this->backfill = $backfill;
        $this->filter = $filter;
        $this->retry = $retry;
        $this->enqueueJob = $enqueueJob;
    }
    public function type(): string
    {
        return self::TYPE;
    }
    public function isUnique(): bool
    {
        return \true;
    }
    public function execute(ContextInterface $context, JobRepository $repository, LoggerInterface $logger): bool
    {
        $args = (array) $context->args();
        $run = (string) ($args['run'] ?? '');
        $page = (int) ($args['page'] ?? 0);
        if (!$this->backfill->isActive($run)) {
            return \true;
        }
        if (!$this->backfill->isEnabled()) {
            $this->backfill->finish($run);
            return \true;
        }
        try {
            $this->importPage($run, (string) ($args['cursor'] ?? ''), (string) ($args['startDate'] ?? ''), $page);
        } catch (Throwable $throwable) {
            $this->retryLater($args, $throwable, $logger);
        }
        return \true;
    }
    private function importPage(string $run, string $cursor, string $startDate, int $page): void
    {
        $history = $this->purchases->list($cursor, self::PAGE_SIZE, $startDate !== '' ? new DateTimeImmutable($startDate) : null);
        $purchases = $history->purchases()->all();
        $queued = 0;
        foreach ($purchases as $purchase) {
            if (!$this->filter->allows($purchase) || $this->isSynced($purchase)) {
                continue;
            }
            // The same UUID the webhook queues, so the queue can drop the duplicate.
            ($this->enqueueJob)(CreateOrderJob::TYPE, ['purchaseUuid' => $purchase->uuid1()]);
            $queued++;
        }
        $this->backfill->recordPage($run, $queued);
        $nextCursor = $history->lastPurchaseHash();
        // The response only carries a hash while more pages remain; a stuck cursor would loop.
        if (count($purchases) < self::PAGE_SIZE || $nextCursor === '' || $nextCursor === $cursor) {
            $this->backfill->finish($run);
            return;
        }
        ($this->enqueueJob)(self::TYPE, ['run' => $run, 'startDate' => $startDate, 'cursor' => $nextCursor, 'page' => $page + 1]);
    }
    private function isSynced(Purchase $purchase): bool
    {
        return $purchase->isRefund() ? $this->refundFactory->isRecorded($purchase) : $this->orderFactory->orderExists($purchase->uuid());
    }
    /**
     * @param array<string, mixed> $args
     */
    private function retryLater(array $args, Throwable $throwable, LoggerInterface $logger): void
    {
        $run = (string) ($args['run'] ?? '');
        $error = $throwable->getMessage();
        $delay = $this->retry->retry(self::TYPE, $args);
        $logger->error(sprintf('Backfill failed on purchase page %d: %s %s', (int) ($args['page'] ?? 0), $error, $delay !== null ? "Retrying in {$delay}s." : 'Giving up.'));
        // Shown on the Tools page; the run only fails once no retries are left.
        if ($delay === null) {
            $this->backfill->finish($run, $error);
            return;
        }
        $this->backfill->recordError($run, $error);
    }
}
