<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job;

/**
 * Re-queues a job later through WP-Cron. The queue retries failed jobs immediately, which
 * doesn't help against API rate limits or short outages; this backs off instead.
 */
class DelayedRetry
{
    public const HOOK = 'paypal-pos.webhook.delayed-job';
    public const MAX_ATTEMPTS = 5;
    private const BASE_DELAY_SECONDS = 60;
    /**
     * @var callable
     */
    private $enqueueJob;
    public function __construct(callable $enqueueJob)
    {
        $this->enqueueJob = $enqueueJob;
    }
    /**
     * Schedules the next attempt after 1, 2, 4, 8 and 16 minutes.
     *
     * @param array<string, mixed> $args The failed job's args; `attempt` counts the retries.
     *
     * @return int|null The delay in seconds, or null when no attempts are left.
     */
    public function retry(string $type, array $args): ?int
    {
        $attempt = (int) ($args['attempt'] ?? 0) + 1;
        if ($attempt > self::MAX_ATTEMPTS) {
            return null;
        }
        $delay = self::BASE_DELAY_SECONDS * 2 ** ($attempt - 1);
        $this->later($type, array_merge($args, ['attempt' => $attempt]), $delay);
        return $delay;
    }
    /**
     * @param array<string, mixed> $args
     */
    private function later(string $type, array $args, int $delaySeconds): void
    {
        wp_schedule_single_event(time() + $delaySeconds, self::HOOK, [$type, $args]);
    }
    /**
     * @internal Callback for the cron event.
     *
     * @param array<string, mixed> $args
     */
    public function enqueue(string $type, array $args): void
    {
        ($this->enqueueJob)($type, $args);
    }
}
