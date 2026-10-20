<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Handler;

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Syde\PayPal\PointOfSale\Webhooks\EventName;
use Syde\PayPal\PointOfSale\Webhooks\Job\CreateOrderJob;

class PurchaseCreatedHandler implements WebhookHandler
{
    /**
     * @var callable
     */
    private $enqueueJob;

    private string $queueCronHook;

    public function __construct(callable $enqueueJob, string $queueCronHook)
    {
        $this->enqueueJob = $enqueueJob;
        $this->queueCronHook = $queueCronHook;
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
            return;
        }

        ($this->enqueueJob)(CreateOrderJob::TYPE, ['purchaseUuid' => $uuid]);

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
