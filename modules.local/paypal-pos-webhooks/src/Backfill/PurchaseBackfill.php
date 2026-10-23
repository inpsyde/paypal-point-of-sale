<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Backfill;

use DateTimeImmutable;
use DateTimeInterface;
use Syde\PayPal\PointOfSale\Webhooks\Job\BackfillJob;

/**
 * Starts and tracks imports of Zettle purchase history. The paging itself runs in
 * BackfillJob, one page per queue run, so no request has to import the whole history.
 */
class PurchaseBackfill
{
    public const STATUS_OPTION = 'paypal-pos.backfill.status';
    public const DEACTIVATED_AT_OPTION = 'paypal-pos.order-sync.deactivated-at';
    public const SETUP_IMPORT_OPTION = 'paypal-pos.backfill.setup-import';
    // Purchases can reach the API a little after their timestamp; re-scan a margin.
    private const OVERLAP_SECONDS = 3600;
    private const MISSED_WINDOW_DAYS = 30;
    // A run that hasn't reported for this long is treated as gone; retries span half an hour.
    private const STALE_SECONDS = 3600;

    /**
     * @var callable
     */
    private $enqueueJob;

    private bool $enabled;

    /**
     * @var callable(): bool
     */
    private $isConnected;

    private bool $autoRecovery;

    private bool $importOnSetup;

    public function __construct(
        callable $enqueueJob,
        bool $enabled,
        callable $isConnected,
        bool $autoRecovery = true,
        bool $importOnSetup = true
    ) {

        $this->enqueueJob = $enqueueJob;
        $this->enabled = $enabled;
        $this->isConnected = $isConnected;
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
     * $replaceRunning supersedes a running import, which stops at its next page.
     */
    public function start(?DateTimeInterface $since = null, bool $replaceRunning = false): bool
    {
        if (!$this->enabled || (!$replaceRunning && $this->isRunning())) {
            return false;
        }

        $run = uniqid('', true);
        $this->saveStatus(['run' => $run, 'state' => 'running', 'queued' => 0, 'pages' => 0, 'error' => '']);
        ($this->enqueueJob)(BackfillJob::TYPE, [
            'run' => $run,
            'startDate' => $since !== null ? $since->format(DateTimeInterface::ATOM) : '',
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
        update_option(self::DEACTIVATED_AT_OPTION, time(), false);
        if ($this->isRunning()) {
            $this->finish(
                $this->status()['run'],
                __('Stopped because the plugin was deactivated.', 'paypal-point-of-sale')
            );
        }
    }

    /**
     * Imports the whole history once, when onboarding completes for the first time. It replaces
     * a running import, which the whole history covers.
     */
    public function startSetupImport(): bool
    {
        if (!$this->importOnSetup || get_option(self::SETUP_IMPORT_OPTION) || !$this->start(null, true)) {
            return false;
        }

        update_option(self::SETUP_IMPORT_OPTION, time(), false);

        return true;
    }

    /**
     * Imports what was sold while the plugin was inactive. Does nothing on first activation,
     * when switched off in the settings, or when the account was disconnected meanwhile.
     */
    public function recoverAfterReactivation(): bool
    {
        $deactivatedAt = (int) get_option(self::DEACTIVATED_AT_OPTION, 0);
        delete_option(self::DEACTIVATED_AT_OPTION);
        if ($deactivatedAt <= 0 || !$this->autoRecovery || !($this->isConnected)()) {
            return false;
        }

        // The inactive period must be imported even if an older import is still running.
        return $this->start(new DateTimeImmutable('@' . ($deactivatedAt - self::OVERLAP_SECONDS)), true);
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
    }

    /**
     * @return array{run: string, state: string, queued: int, pages: int, error: string, updated: int}
     */
    public function status(): array
    {
        $status = (array) get_option(self::STATUS_OPTION, []);

        return [
            'run' => (string) ($status['run'] ?? ''),
            'state' => (string) ($status['state'] ?? ''),
            'queued' => (int) ($status['queued'] ?? 0),
            'pages' => (int) ($status['pages'] ?? 0),
            'error' => (string) ($status['error'] ?? ''),
            'updated' => (int) ($status['updated'] ?? 0),
        ];
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
