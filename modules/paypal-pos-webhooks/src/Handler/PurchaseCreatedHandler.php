<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Handler;

use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\EventName;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job\CreateOrderJob;
class PurchaseCreatedHandler implements WebhookHandler
{
    /**
     * @var callable
     */
    private $enqueueJob;
    private string $queueCronHook;
    private LoggerInterface $logger;
    public function __construct(callable $enqueueJob, string $queueCronHook, LoggerInterface $logger)
    {
        $this->enqueueJob = $enqueueJob;
        $this->queueCronHook = $queueCronHook;
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
     * Runs the queue now via cron: webhooks have no admin session for the loopback runner.
     */
    private function dispatchQueue(): void
    {
        if (!wp_next_scheduled($this->queueCronHook)) {
            wp_schedule_single_event(time() - 1, $this->queueCronHook);
        }
        if (function_exists('spawn_cron')) {
            spawn_cron();
        }
    }
}
