<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags;

use Psr\Container\ContainerInterface as C;

return [
    'paypal-pos.feature-flags.properties' => static function (array $previous, C $container): array {
        if (!$container->get('paypal-pos.feature-flags.qa-site')) {
            return $previous;
        }

        return ['qaSite' => 'true'] + $previous;
    },

    /*
     * Shown regardless of the onboarding state, so support can also check sites that aren't
     * connected to PayPal POS yet.
     */
    'inpsyde.wc-status-report.items' => static function (array $items, C $container): array {
        $flags = $container->get('paypal-pos.feature-flags');
        assert($flags instanceof Flags);
        $report = $container->get('paypal-pos.feature-flags.status-report');
        assert($report instanceof StatusReportItems);

        return array_merge(
            $items,
            $report->items($flags->connectionStatus(), $flags->states(), $flags->configurationError())
        );
    },
];
