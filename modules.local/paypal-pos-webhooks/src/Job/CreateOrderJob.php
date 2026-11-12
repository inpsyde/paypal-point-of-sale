<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Job;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Purchases\Purchases;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseRefundFactory;
use Throwable;

class CreateOrderJob implements Job
{
    public const TYPE = 'webhook-purchase-created';

    private Purchases $purchases;

    private PurchaseOrderFactory $orderFactory;

    private PurchaseRefundFactory $refundFactory;

    public function __construct(
        Purchases $purchases,
        PurchaseOrderFactory $orderFactory,
        PurchaseRefundFactory $refundFactory
    ) {

        $this->purchases = $purchases;
        $this->orderFactory = $orderFactory;
        $this->refundFactory = $refundFactory;
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

        $uuid = (string) ($context->args()->purchaseUuid ?? '');
        if ($uuid === '') {
            return true;
        }

        try {
            $purchase = $this->purchases->read($uuid);

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
            // Returning false lets the queue retry; any other throw would be dropped.
            $logger->error(
                sprintf('CreateOrderJob failed for purchase %s: %s', $uuid, $throwable->getMessage())
            );

            return false;
        }
    }
}
