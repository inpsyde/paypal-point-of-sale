<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Syde\PayPal\PointOfSale\DeprecatedHooks;
use Syde\PayPal\PointOfSale\Test\MonkeryTestCase;

use function Brain\Monkey\Functions\expect;

/**
 * The maps below deliberately restate the contract instead of reading the (private) constants
 * of the class under test, so that a typo or a dropped entry fails a test instead of silently
 * agreeing with itself.
 */
class DeprecatedHooksTest extends MonkeryTestCase
{
    private const RENAMED_IN = '2.0.0';

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

    private const ACTIONS = [
        'zettle-pos-integration.init' => 'paypal-point-of-sale.init',
        'zettle-pos-integration.migrate' => 'paypal-point-of-sale.migrate',
        'zettle-pos-integration.activate' => 'paypal-point-of-sale.activate',
        'zettle-pos-integration.deactivate' => 'paypal-point-of-sale.deactivate',
        'zettle.clear-product-cache' => 'paypal-pos.clear-product-cache',
    ];

    /**
     * The deprecated filter is dispatched through core's `apply_filters_deprecated()`, which is
     * what triggers `_deprecated_hook()`, and its result is handed back to the current filter.
     *
     * @dataProvider provideFilters
     */
    public function testFilterBridgeDelegatesToApplyFiltersDeprecated(
        string $deprecated,
        string $current
    ): void {

        $bridge = $this->captureFilterBridge($current);

        expect('apply_filters_deprecated')
            ->once()
            ->with(
                $deprecated,
                ['a value', 'an extra arg'],
                self::RENAMED_IN,
                $current
            )
            ->andReturn('a filtered value');

        static::assertSame('a filtered value', $bridge('a value', 'an extra arg'));
    }

    /**
     * `do_action()` substitutes an empty string for argument-less actions and
     * `do_action_ref_array()` does not re-create it, so the bridge has to forward it verbatim -
     * otherwise a legacy callback declaring a parameter would hit an ArgumentCountError.
     *
     * @dataProvider provideActions
     */
    public function testActionBridgeDelegatesToDoActionDeprecated(
        string $deprecated,
        string $current
    ): void {

        $bridge = $this->captureActionBridge($current);

        expect('do_action_deprecated')
            ->once()
            ->with($deprecated, [''], self::RENAMED_IN, $current);

        $bridge('');
    }

    /**
     * With nothing attached to the deprecated hook, core returns `$args[0]` untouched, so the
     * current hook keeps the exact value it would have had without the compatibility layer.
     */
    public function testFilterBridgeReturnsTheValueUnchangedWhenNobodyListens(): void
    {
        $bridge = $this->captureFilterBridge('paypal-point-of-sale.http.client');

        static::assertSame('wp', $bridge('wp'));
    }

    public function provideFilters(): array
    {
        return $this->cases(self::FILTERS);
    }

    public function provideActions(): array
    {
        return $this->cases(self::ACTIONS);
    }

    private function cases(array $map): array
    {
        $cases = [];
        foreach ($map as $deprecated => $current) {
            $cases[$deprecated] = [$deprecated, $current];
        }

        return $cases;
    }

    private function captureFilterBridge(string $current): callable
    {
        $bridge = null;

        Filters\expectAdded($current)
            ->once()
            ->whenHappen(function ($callback, $priority, $acceptedArgs) use (&$bridge): void {
                $bridge = $callback;
                static::assertSame(10, $priority);
                static::assertGreaterThanOrEqual(10, $acceptedArgs);
            });

        (new DeprecatedHooks())->register();

        static::assertIsCallable($bridge);

        return $bridge;
    }

    private function captureActionBridge(string $current): callable
    {
        $bridge = null;

        Actions\expectAdded($current)
            ->once()
            ->whenHappen(function ($callback, $priority, $acceptedArgs) use (&$bridge): void {
                $bridge = $callback;
                static::assertSame(10, $priority);
                static::assertGreaterThanOrEqual(10, $acceptedArgs);
            });

        (new DeprecatedHooks())->register();

        static::assertIsCallable($bridge);

        return $bridge;
    }
}
