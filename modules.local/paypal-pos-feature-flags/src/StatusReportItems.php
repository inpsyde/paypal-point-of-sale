<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags;

use Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionSource;
use Inpsyde\FeatureFlags\Adapter\Unleash\Status\ConnectionStatus;
use Inpsyde\WcStatusReport\ReportItemFactoryInterface;
use Inpsyde\WcStatusReport\ReportItemInterface;

/**
 * The feature flag items of the PayPal POS section in the WooCommerce status report. Values
 * are in English, so support can read them in exported reports.
 */
final class StatusReportItems
{
    public function __construct(
        private readonly ReportItemFactoryInterface $factory,
        private readonly string $siteId,
        private readonly bool $overridden,
        private readonly bool $qaSite
    ) {
    }

    /**
     * @param array<string, bool> $flags Whether each flag of the project is on for this site.
     * @param string|null $configurationError Why the project isn't connected, if it isn't.
     * @return list<ReportItemInterface>
     */
    public function items(
        ConnectionStatus $connection,
        array $flags,
        ?string $configurationError = null
    ): array {

        return [
            $this->factory->createReportItem(
                __('Feature flags site ID', 'paypal-point-of-sale'),
                'Feature flags site ID',
                $this->siteId
            ),
            $this->factory->createReportItem(
                __('Feature flags configuration', 'paypal-point-of-sale'),
                'Feature flags configuration',
                sprintf(
                    '%s, QA site: %s',
                    $this->overridden ? 'overridden' : 'built-in',
                    $this->qaSite ? 'yes' : 'no'
                )
            ),
            $this->factory->createReportItem(
                __('Feature flags source', 'paypal-point-of-sale'),
                'Feature flags source',
                match ($connection->source) {
                    ConnectionSource::Fresh => 'fresh',
                    ConnectionSource::Stale => 'stale copy (Unleash unreachable)',
                    ConnectionSource::Defaults => 'code defaults (no usable copy)',
                    ConnectionSource::Unknown => 'not fetched yet',
                }
            ),
            $this->factory->createReportItem(
                __('Feature flags last successful fetch', 'paypal-point-of-sale'),
                'Feature flags last successful fetch',
                $connection->lastSuccessTime === null
                    ? 'never'
                    : $this->time($connection->lastSuccessTime)
            ),
            $this->factory->createReportItem(
                __('Feature flags last error', 'paypal-point-of-sale'),
                'Feature flags last error',
                $this->lastError($connection, $configurationError)
            ),
            $this->factory->createReportItem(
                __('Feature flags', 'paypal-point-of-sale'),
                'Feature flags',
                $this->flags($flags)
            ),
        ];
    }

    /**
     * The library's message never contains the token or request headers.
     */
    private function lastError(ConnectionStatus $connection, ?string $configurationError): string
    {
        if ($configurationError !== null) {
            return $configurationError;
        }

        $error = $connection->lastError;

        return $error === null ? 'none' : "{$this->time($error->time)}: {$error->message}";
    }

    /**
     * @param array<string, bool> $flags
     */
    private function flags(array $flags): string
    {
        if ($flags === []) {
            return 'none';
        }
        ksort($flags);

        $states = [];
        foreach ($flags as $name => $on) {
            $states[] = (string) $name . ': ' . ($on ? 'on' : 'off');
        }

        return implode(', ', $states);
    }

    private function time(int $timestamp): string
    {
        return gmdate('Y-m-d H:i:s', $timestamp) . ' UTC';
    }
}
