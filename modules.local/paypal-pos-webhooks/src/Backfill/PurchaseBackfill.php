<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Backfill;

use DateTimeImmutable;
use DateTimeInterface;
use Syde\PayPal\PointOfSale\Container\WritableContainerInterface;
use Syde\PayPal\PointOfSale\Webhooks\Job\BackfillJob;

/**
 * Starts and tracks imports of Zettle purchase history. The paging itself runs in
 * BackfillJob, one page per queue run, so no request has to import the whole history.
 */
class PurchaseBackfill
{
    // Own options, not setup info: queue runs write them often, and setup info is rewritten as a
    // whole on every write.
    public const STATUS_OPTION = 'paypal-pos.backfill.status';
    public const WEBHOOK_GAP_OPTION = 'paypal-pos.backfill.webhook-gap-since';
    public const DEACTIVATED_AT_KEY = 'order_sync_deactivated_at';
    public const SETUP_IMPORT_KEY = 'order_sync_setup_import_at';
    // Purchases can reach the API a little after their timestamp; re-scan a margin.
    private const OVERLAP_SECONDS = 3600;
    private const MISSED_WINDOW_DAYS = 30;
    // A run that hasn't reported for this long is treated as gone; retries span about 40 minutes.
    private const STALE_SECONDS = 3600;

    /**
     * @var callable
     */
    private $enqueueJob;

    private bool $enabled;

    /**
     * @var callable(): bool
     */
    private $isOnboarded;

    private WritableContainerInterface $setupInfo;

    private bool $autoRecovery;

    private bool $importOnSetup;

    public function __construct(
        callable $enqueueJob,
        bool $enabled,
        callable $isOnboarded,
        WritableContainerInterface $setupInfo,
        bool $autoRecovery = true,
        bool $importOnSetup = true
    ) {

        $this->enqueueJob = $enqueueJob;
        $this->enabled = $enabled;
        $this->isOnboarded = $isOnboarded;
        $this->setupInfo = $setupInfo;
        $this->autoRecovery = $autoRecovery;
        $this->importOnSetup = $importOnSetup;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isRunning(): bool
    {
        $status = $this->status();

        return $status['state'] === 'running' && time() - $status['updated'] < self::STALE_SECONDS;
    }

    /**
     * Queues an import of every purchase made at or after $since, or of the whole history.
     * $replaceRunning supersedes a running import, which stops at its next page. An interrupted or
     * superseded import's range is taken over; a failed one's isn't, as it would fail again.
     */
    public function start(?DateTimeInterface $since = null, bool $replaceRunning = false): bool
    {
        if (!$this->enabled || (!$replaceRunning && $this->isRunning())) {
            return false;
        }

        $run = uniqid('', true);
        $startDate = $since !== null ? $since->format(DateTimeInterface::ATOM) : '';
        $last = $this->status();
        if (in_array($last['state'], ['running', 'interrupted'], true)) {
            $startDate = self::earlier($startDate, $last['since']);
        }
        $this->saveStatus(
            ['run' => $run, 'state' => 'running', 'since' => $startDate, 'queued' => 0, 'pages' => 0, 'error' => '']
        );
        ($this->enqueueJob)(BackfillJob::TYPE, [
            'run' => $run,
            'startDate' => $startDate,
            'cursor' => '',
            'page' => 0,
        ]);

        return true;
    }

    /**
     * Queues an import of the last 30 days, catching webhooks lost while the plugin was active.
     */
    public function startMissed(): bool
    {
        return $this->start(new DateTimeImmutable(sprintf('-%d days', self::MISSED_WINDOW_DAYS)));
    }

    /**
     * Remembers when the plugin was deactivated, and stops a running import; its queued page
     * stops when it runs.
     */
    public function rememberDeactivation(): void
    {
        $this->setupInfo->set(self::DEACTIVATED_AT_KEY, time());
        if ($this->isRunning()) {
            $status = $this->status();
            $status['state'] = 'interrupted';
            $status['error'] = __('Stopped because the plugin was deactivated.', 'paypal-point-of-sale');
            $this->saveStatus($status);
        }
    }

    /**
     * Imports the whole history once, when onboarding completes for the first time. It replaces
     * a running import, which the whole history covers.
     */
    public function startSetupImport(): bool
    {
        if (!$this->importOnSetup || $this->setupInfo->has(self::SETUP_IMPORT_KEY) || !$this->start(null, true)) {
            return false;
        }

        $this->setupInfo->set(self::SETUP_IMPORT_KEY, time());

        return true;
    }

    /**
     * Imports what was sold while the plugin was inactive. Does nothing on first activation,
     * when switched off in the settings, or before onboarding is completed.
     */
    public function recoverAfterReactivation(): bool
    {
        if (!$this->setupInfo->has(self::DEACTIVATED_AT_KEY)) {
            return false;
        }
        $deactivatedAt = (int) $this->setupInfo->get(self::DEACTIVATED_AT_KEY);
        $this->setupInfo->unset(self::DEACTIVATED_AT_KEY);
        if ($deactivatedAt <= 0 || !$this->autoRecovery || !($this->isOnboarded)()) {
            return false;
        }

        // The inactive period must be imported even if an older import is still running.
        return $this->start(new DateTimeImmutable('@' . ($deactivatedAt - self::OVERLAP_SECONDS)), true);
    }

    /**
     * Keeps the first failure. Skipped during onboarding, where the setup import choice decides
     * about past sales.
     */
    public function rememberWebhookGap(): void
    {
        if (($this->isOnboarded)() && !get_option(self::WEBHOOK_GAP_OPTION)) {
            update_option(self::WEBHOOK_GAP_OPTION, time(), false);
        }
    }

    /**
     * A running import pages up to the present: one that started before the gap covers it, so the
     * gap is kept until that import finishes; a later one is replaced by an import from the gap.
     */
    public function recoverWebhookGap(): bool
    {
        $since = $this->webhookGapSince();
        if ($since === null || !($this->isOnboarded)()) {
            return false;
        }
        $running = $this->isRunning() ? $this->status()['since'] : null;
        if ($running !== null && self::covers($running, $since)) {
            return false;
        }
        delete_option(self::WEBHOOK_GAP_OPTION);

        return $this->start($since, $running !== null);
    }

    /**
     * Whether $run should import its next page: it's the current import and wasn't stopped.
     */
    public function isActive(string $run): bool
    {
        return $this->isCurrent($run) && $this->status()['state'] === 'running';
    }

    public function recordPage(string $run, int $queued): void
    {
        if (!$this->isCurrent($run)) {
            return;
        }

        $status = $this->status();
        // A page that succeeds after a retry clears the earlier error.
        $status['state'] = 'running';
        $status['error'] = '';
        $status['queued'] += $queued;
        $status['pages']++;
        $this->saveStatus($status);
    }

    /**
     * Shows an error while the queue still retries the page; the run keeps running.
     */
    public function recordError(string $run, string $error): void
    {
        if (!$this->isCurrent($run)) {
            return;
        }

        $status = $this->status();
        $status['error'] = $error;
        $this->saveStatus($status);
    }

    public function finish(string $run, string $error = ''): void
    {
        if (!$this->isCurrent($run)) {
            return;
        }

        $status = $this->status();
        $status['state'] = $error === '' ? 'done' : 'failed';
        $status['error'] = $error;
        $this->saveStatus($status);

        // A gap this import covered is imported on its own when the import failed.
        $gapSince = $this->webhookGapSince();
        if ($error !== '') {
            $this->recoverWebhookGap();
        } elseif ($gapSince !== null && self::covers($status['since'], $gapSince)) {
            delete_option(self::WEBHOOK_GAP_OPTION);
        }
    }

    private function webhookGapSince(): ?DateTimeImmutable
    {
        $gapStart = (int) get_option(self::WEBHOOK_GAP_OPTION, 0);

        return $gapStart > 0 ? new DateTimeImmutable('@' . ($gapStart - self::OVERLAP_SECONDS)) : null;
    }

    /**
     * @param string $startDate An import's ATOM start date, or empty for the whole history.
     */
    private static function covers(string $startDate, DateTimeImmutable $since): bool
    {
        return $startDate === '' || new DateTimeImmutable($startDate) <= $since;
    }

    /**
     * @return array{
     *     run: string,
     *     state: string,
     *     since: string,
     *     queued: int,
     *     pages: int,
     *     error: string,
     *     updated: int
     * } `since` is the run's start date, empty for the whole history.
     */
    public function status(): array
    {
        $status = (array) get_option(self::STATUS_OPTION, []);

        return [
            'run' => (string) ($status['run'] ?? ''),
            'state' => (string) ($status['state'] ?? ''),
            'since' => (string) ($status['since'] ?? ''),
            'queued' => (int) ($status['queued'] ?? 0),
            'pages' => (int) ($status['pages'] ?? 0),
            'error' => (string) ($status['error'] ?? ''),
            'updated' => (int) ($status['updated'] ?? 0),
        ];
    }

    /**
     * @param string $startDate ATOM date, or empty for the whole history.
     */
    private static function earlier(string $startDate, string $other): string
    {
        if ($startDate === '' || $other === '') {
            return '';
        }

        return new DateTimeImmutable($other) < new DateTimeImmutable($startDate) ? $other : $startDate;
    }

    private function isCurrent(string $run): bool
    {
        return $this->status()['run'] === $run;
    }

    /**
     * @param array<string, mixed> $status
     */
    private function saveStatus(array $status): void
    {
        $status['updated'] = time();
        update_option(self::STATUS_OPTION, $status, false);
    }
}
