<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Job;

use DateTimeImmutable;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContextInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseFilter;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseRefundFactory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Purchases\Purchases;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
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
    private int $maxRetries;
    /**
     * @var callable
     */
    private $enqueueJob;
    public function __construct(Purchases $purchases, PurchaseOrderFactory $orderFactory, PurchaseRefundFactory $refundFactory, PurchaseBackfill $backfill, PurchaseFilter $filter, int $maxRetries, callable $enqueueJob)
    {
        $this->purchases = $purchases;
        $this->orderFactory = $orderFactory;
        $this->refundFactory = $refundFactory;
        $this->backfill = $backfill;
        $this->filter = $filter;
        $this->maxRetries = $maxRetries;
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
            return $this->failPage($run, $page, $context->retryCount(), $throwable, $logger);
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
     * @return bool False while retries are left, so the queue retries the page with its backoff.
     */
    private function failPage(string $run, int $page, int $retryCount, Throwable $throwable, LoggerInterface $logger): bool
    {
        $error = $throwable->getMessage();
        $retriesLeft = $retryCount < $this->maxRetries;
        $logger->error(sprintf('Backfill failed on purchase page %d: %s %s', $page, $error, $retriesLeft ? 'Retrying.' : 'Giving up.'));
        // Shown on the Tools page; the run only fails once no retries are left.
        if (!$retriesLeft) {
            $this->backfill->finish($run, $error);
            return \true;
        }
        $this->backfill->recordError($run, $error);
        return \false;
    }
}
