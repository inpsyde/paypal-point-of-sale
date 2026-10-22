<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Handler;

use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Syde\PayPal\PointOfSale\Webhooks\EventName;
use Syde\PayPal\PointOfSale\Webhooks\Job\CreateOrderJob;

class PurchaseCreatedHandler implements WebhookHandler
{
    /**
     * @var callable
     */
    private $enqueueJob;

    private LoggerInterface $logger;

    public function __construct(callable $enqueueJob, LoggerInterface $logger)
    {
        $this->enqueueJob = $enqueueJob;
        $this->logger = $logger;
    }

    public function accepts(Payload $payload): bool
    {
        return $payload->eventName() === EventName::PURCHASE_CREATED;
    }

    public function handle(Payload $payload): void
    {
        $data = $payload->payload();
        // The webhook sends `purchaseUuid`, the REST API `purchaseUUID`.
        $uuid = (string) ($data['purchaseUuid'] ?? $data['purchaseUUID'] ?? $data['uuid'] ?? '');
        if ($uuid === '') {
            $this->logger->warning(sprintf('Ignoring %s event without a purchase UUID', EventName::PURCHASE_CREATED));

            return;
        }

        ($this->enqueueJob)(CreateOrderJob::TYPE, ['purchaseUuid' => $uuid]);
        $this->logger->info(sprintf('Queued order creation for purchase %s', $uuid));

        $this->dispatchQueue();
    }

    /**
     * Runs the queue now instead of on the next visit: webhooks have no admin session for the
     * loopback runner, and the queue's cron event is always scheduled.
     */
    private function dispatchQueue(): void
    {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return;
        }

        if (function_exists('spawn_cron')) {
            spawn_cron();
        }
    }
}
