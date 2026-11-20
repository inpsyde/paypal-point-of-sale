<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\WcStatusReport\ReportItemFactoryInterface;
use Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;

/**
 * Gives the other modules the flags that the bootstrap connected before loading them.
 */
class FeatureFlagsModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public function __construct(private readonly Flags $flags)
    {
    }

    /**
     * @inheritDoc
     */
    public function services(): array
    {
        $flags = $this->flags;

        return [
            'paypal-pos.feature-flags' => static fn (): Flags => $flags,
        ];
    }

    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        $flags = $this->flags;

        return [
            /*
             * Shown regardless of the onboarding state, so support can also check sites that
             * aren't connected to PayPal POS yet.
             */
            'inpsyde.wc-status-report.items' =>
                static function (array $items, C $container) use ($flags): array {
                    $factory = $container->get('inpsyde.wc-status-report.item-factory');
                    assert($factory instanceof ReportItemFactoryInterface);
                    $report = new StatusReportItems(
                        $factory,
                        $flags->siteId(),
                        $flags->settings()->overridden,
                        $flags->settings()->qaSite
                    );
                    // First: reading the states fetches the flags if due, which updates the
                    // connection status.
                    $states = $flags->states();

                    return array_merge(
                        $items,
                        $report->items(
                            $flags->connectionStatus(),
                            $states,
                            $flags->configurationError()
                        )
                    );
                },
        ];
    }

    /**
     * The logger is a service, so a configuration error from the boot is logged here.
     */
    public function run(C $container): bool
    {
        $error = $this->flags->configurationError();
        if ($error === null) {
            return true;
        }

        // An error persists on every request until it is fixed.
        $key = 'paypal_pos_feature_flags_error_' . md5($error);
        if (get_transient($key) === false) {
            set_transient($key, 1, DAY_IN_SECONDS);

            $logger = $container->get('paypal-pos.logger');
            assert($logger instanceof LoggerInterface);
            $logger->error('Feature flags are not available: {message}', ['message' => $error]);
        }

        return true;
    }
}
