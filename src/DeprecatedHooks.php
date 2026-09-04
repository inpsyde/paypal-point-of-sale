<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale;

/**
 * Keeps the hook names used before the 2.0.0 rebranding working.
 *
 * For every renamed hook a bridge callback is attached to the current hook. The bridge
 * re-fires the deprecated hook through WP core's `apply_filters_deprecated()` /
 * `do_action_deprecated()`, which call `_deprecated_hook()` and then run whatever is still
 * attached to the old name. Core short-circuits both helpers when nothing is attached to the
 * deprecated hook, so sites already using the new names see no notice.
 *
 * Known limitations:
 * - Callbacks attached to a deprecated hook keep their order among themselves, but all run at
 *   the bridge's single priority (10) on the current hook rather than at their own. Since the
 *   bridge is registered during bootstrap, it precedes anything else added at priority 10, so
 *   in practice the deprecated chain runs first among those.
 * - Neither `apply_filters_deprecated()` nor `do_action_deprecated()` bumps a hook counter
 *   itself, so the counter for a deprecated name therefore tracks "did this actually run something",
 *   not "did the event happen". That is, code that probes with `did_action()` instead of
 *   attaching a callback is not covered.
 */
class DeprecatedHooks
{
    /**
     * Deprecated filter name => current filter name.
     *
     * @var array<string, string>
     */
    private const FILTERS = [
        'zettle-pos-integration.barcode.value' =>
            'paypal-point-of-sale.barcode.value',
        'zettle-pos-integration.barcode.standard-input-ui-enabled' =>
            'paypal-point-of-sale.barcode.standard-input-ui-enabled',
        'zettle-pos-integration.http.client' =>
            'paypal-point-of-sale.http.client',
        'zettle-pos-integration.sync.clear-cache-before-stock-sync' =>
            'paypal-point-of-sale.sync.clear-cache-before-stock-sync',
    ];

    /**
     * Deprecated action name => current action name.
     *
     * @var array<string, string>
     */
    private const ACTIONS = [
        'zettle-pos-integration.init' => 'paypal-point-of-sale.init',
        'zettle-pos-integration.migrate' => 'paypal-point-of-sale.migrate',
        'zettle-pos-integration.activate' => 'paypal-point-of-sale.activate',
        'zettle-pos-integration.deactivate' => 'paypal-point-of-sale.deactivate',
        'zettle.clear-product-cache' => 'paypal-pos.clear-product-cache',
    ];

    /**
     * The plugin version the hooks above were renamed in.
     */
    private const DEPRECATED_SINCE = '2.0.0';

    /**
     * Passed as `$accepted_args` so a bridge always receives every argument the current hook was
     * fired with: `WP_Hook::apply_filters()` only slices when `accepted_args` is lower than the
     * number of passed arguments. Forwarding verbatim keeps the deprecated hook's payload
     * identical - including the empty string `do_action()` substitutes for argument-less actions.
     */
    private const ACCEPT_ALL_ARGS = PHP_INT_MAX;

    public function register(): void
    {
        foreach (self::FILTERS as $deprecated => $current) {
            add_filter(
                $current,
                static function (mixed ...$args) use ($deprecated, $current): mixed {
                    return apply_filters_deprecated(
                        $deprecated,
                        $args,
                        self::DEPRECATED_SINCE,
                        $current
                    );
                },
                10,
                self::ACCEPT_ALL_ARGS
            );
        }

        foreach (self::ACTIONS as $deprecated => $current) {
            add_action(
                $current,
                static function (mixed ...$args) use ($deprecated, $current): void {
                    do_action_deprecated(
                        $deprecated,
                        $args,
                        self::DEPRECATED_SINCE,
                        $current
                    );
                },
                10,
                self::ACCEPT_ALL_ARGS
            );
        }
    }
}
