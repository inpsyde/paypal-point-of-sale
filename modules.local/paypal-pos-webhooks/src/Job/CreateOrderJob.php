<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Job;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Purchases\Purchases;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseFilter;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseRefundFactory;
use Throwable;

class CreateOrderJob implements Job
{
    public const TYPE = 'webhook-purchase-created';

    private Purchases $purchases;

    private PurchaseOrderFactory $orderFactory;

    private PurchaseRefundFactory $refundFactory;

    private bool $enabled;

    private PurchaseFilter $filter;

    private DelayedRetry $retry;

    public function __construct(
        Purchases $purchases,
        PurchaseOrderFactory $orderFactory,
        PurchaseRefundFactory $refundFactory,
        bool $enabled,
        PurchaseFilter $filter,
        DelayedRetry $retry
    ) {

        $this->purchases = $purchases;
        $this->orderFactory = $orderFactory;
        $this->refundFactory = $refundFactory;
        $this->enabled = $enabled;
        $this->filter = $filter;
        $this->retry = $retry;
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function isUnique(): bool
    {
        return true;
    }

    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {

        $args = $context->args();
        $uuid = (string) ($args->purchaseUuid ?? '');
        // Also drops jobs queued before order sync was switched off.
        if ($uuid === '' || !$this->enabled) {
            return true;
        }

        try {
            $purchase = $this->purchases->read($uuid);
            if (!$this->filter->allows($purchase)) {
                return true;
            }

            if ($purchase->isRefund()) {
                $this->refundFactory->createRefund($purchase);

                return true;
            }

            // Orders store the purchase's short UUID; the webhook sends the dashed one.
            if (!$this->orderFactory->orderExists($purchase->uuid())) {
                $orderId = $this->orderFactory->createOrder($purchase);
                $this->refundFactory->linkOrphanRefunds($purchase->uuid(), $orderId);
            }

            return true;
        } catch (Throwable $throwable) {
            return $this->retryLater($uuid, (int) ($args->attempt ?? 0), $throwable, $logger);
        }
    }

    private function retryLater(string $uuid, int $attempt, Throwable $throwable, LoggerInterface $logger): bool
    {
        $message = sprintf('CreateOrderJob failed for purchase %s: %s', $uuid, $throwable->getMessage());
        $delay = $this->retry->retry(self::TYPE, ['purchaseUuid' => $uuid, 'attempt' => $attempt]);
        $logger->error($delay !== null ? "{$message} Retrying in {$delay}s." : "{$message} Giving up.");

        return true;
    }
}
