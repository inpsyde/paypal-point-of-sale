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
        // Zettle's webhook payload uses `purchaseUuid`, while the REST Purchase API uses
        // `purchaseUUID`; accept both so a casing difference cannot silently drop the event.
        $uuid = (string) (
            $data['purchaseUUID']
            ?? $data['purchaseUuid']
            ?? $data['uuid']
            ?? ''
        );
        if ($uuid === '') {
            return;
        }

        ($this->enqueueJob)(CreateOrderJob::TYPE, ['purchaseUuid' => $uuid]);

        $this->dispatchQueue();
    }

    /**
     * Nudge the queue to process now instead of waiting for the next request. The
     * webhook carries no admin session, so the authenticated loopback runner cannot be
     * used; a cron spawn needs no auth.
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
