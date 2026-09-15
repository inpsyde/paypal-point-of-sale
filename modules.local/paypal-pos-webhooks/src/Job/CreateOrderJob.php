<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Job;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Purchases\Purchases;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use Throwable;

class CreateOrderJob implements Job
{
    public const TYPE = 'create-order';

    private Purchases $purchases;

    private PurchaseOrderFactory $orderFactory;

    public function __construct(Purchases $purchases, PurchaseOrderFactory $orderFactory)
    {
        $this->purchases = $purchases;
        $this->orderFactory = $orderFactory;
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
            if ($this->orderFactory->orderExists($uuid)) {
                return true;
            }

            $this->orderFactory->createOrder($this->purchases->read($uuid));

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
