<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Backfill;

/**
 * The PayPal POS import entries on WooCommerce → Status → Tools.
 */
class BackfillTools
{
    private PurchaseBackfill $backfill;

    public function __construct(PurchaseBackfill $backfill)
    {
        $this->backfill = $backfill;
    }

    /**
     * @param array<string, mixed> $tools
     *
     * @return array<string, mixed>
     */
    public function addTools(array $tools): array
    {
        $tools['zettle_import_orders'] = [
            'name' => __('Import PayPal POS orders', 'paypal-point-of-sale'),
            'button' => __('Import historical orders', 'paypal-point-of-sale'),
            'desc' => __(
                'Fetches all past PayPal POS sales and refunds in the background and creates any that are not yet in WooCommerce.',
                'paypal-point-of-sale'
            ) . $this->status(),
            'callback' => fn (): string => $this->startMessage(fn (): bool => $this->backfill->start()),
        ];
        $tools['zettle_sync_missed_orders'] = [
            'name' => __('Sync missed PayPal POS orders', 'paypal-point-of-sale'),
            'button' => __('Sync missed orders', 'paypal-point-of-sale'),
            'desc' => __(
                'Fetches PayPal POS sales and refunds from the last 30 days in the background and creates any that are missing.',
                'paypal-point-of-sale'
            ),
            'callback' => fn (): string => $this->startMessage(fn (): bool => $this->backfill->startMissed()),
        ];

        return $tools;
    }

    /**
     * @param callable(): bool $start
     */
    private function startMessage(callable $start): string
    {
        if (!$this->backfill->isEnabled()) {
            return __('POS order creation is disabled in the PayPal POS settings.', 'paypal-point-of-sale');
        }
        if (!$start()) {
            return __('A PayPal POS import is already running. Try again when it has finished.', 'paypal-point-of-sale');
        }

        return __('PayPal POS sales are being imported in the background.', 'paypal-point-of-sale');
    }

    private function status(): string
    {
        $status = $this->backfill->status();
        if ($status['state'] === '') {
            return '';
        }

        $states = [
            'running' => __('running', 'paypal-point-of-sale'),
            'done' => __('done', 'paypal-point-of-sale'),
            'failed' => __('failed', 'paypal-point-of-sale'),
        ];
        $summary = sprintf(
            /* translators: 1: import state (running, done or failed), 2: number of sales and refunds queued */
            _n(
                'Last import: %1$s, %2$d sale or refund queued.',
                'Last import: %1$s, %2$d sales and refunds queued.',
                $status['queued'],
                'paypal-point-of-sale'
            ),
            $states[$status['state']] ?? $status['state'],
            $status['queued']
        );
        if ($status['error'] !== '') {
            /* translators: %s: error message */
            $summary .= ' ' . sprintf(__('Last error: %s', 'paypal-point-of-sale'), $status['error']);
        }

        return '<br><em>' . esc_html($summary) . '</em>';
    }
}
