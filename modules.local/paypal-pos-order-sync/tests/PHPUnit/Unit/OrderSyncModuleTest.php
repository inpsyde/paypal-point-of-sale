<?php

declare(strict_types=1);

use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PastSalesNotice;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\OrderSync\OrderSyncModule;
use Syde\PayPal\PointOfSale\OrderSync\OrderSyncSettingsFilter;
use Syde\PayPal\PointOfSale\OrderSync\Test\SetupInfoFake;
use function Brain\Monkey\Actions\expectAdded;
use function Brain\Monkey\Functions\when;

class OrderSyncModuleTest extends BrainMonkeyWpTestCase
{
    /**
     * GIVEN a merchant who chose the setup import and has a pending past sales notice
     * WHEN onboarding completes
     * THEN a history import is queued without a start date
     * AND the past sales notice is dismissed
     */
    public function testOnboardingCompletionStartsTheSetupImportAndDismissesTheNotice(): void
    {
        $listeners = [];
        expectAdded('paypal-pos.onboarding.completed')->zeroOrMoreTimes()->whenHappen(
            static function (callable $listener) use (&$listeners): void {
                $listeners[] = $listener;
            }
        );
        $options = [];
        when('get_option')->alias(
            static function (string $name, $default = false) use (&$options) {
                return $options[$name] ?? $default;
            }
        );
        when('update_option')->alias(static function (string $name, $value) use (&$options): bool {
            $options[$name] = $value;

            return true;
        });
        when('delete_option')->alias(static function (string $name) use (&$options): bool {
            unset($options[$name]);

            return true;
        });

        $setupInfo = new SetupInfoFake([PastSalesNotice::PENDING_KEY => 1700000000]);
        $enqueued = [];
        $services = [
            'paypal-pos.order-sync.backfill' => new PurchaseBackfill(
                static function (string $type, array $args) use (&$enqueued): void {
                    $enqueued[] = [$type, $args];
                },
                true,
                static fn (): bool => true,
                $setupInfo->container(),
                true,
                true
            ),
            'paypal-pos.order-sync.past-sales-notice' => new PastSalesNotice($setupInfo->container(), true),
            'paypal-pos.order-sync.settings-filter' => new OrderSyncSettingsFilter(),
            'paypal-pos.settings.wc-integration.id' => 'paypal-pos',
        ];
        $container = Mockery::mock(ContainerInterface::class);
        $container->allows('get')->andReturnUsing(static fn (string $id) => $services[$id]);

        (new OrderSyncModule())->run($container);
        foreach ($listeners as $listener) {
            $listener();
        }

        self::assertCount(1, $enqueued);
        self::assertSame('', $enqueued[0][1]['startDate']);
        self::assertFalse($setupInfo->has(PastSalesNotice::PENDING_KEY));
    }

    /**
     * GIVEN a pending past sales notice
     * WHEN WooCommerce fires its hide-notice action for that notice
     * THEN the notice is no longer pending
     */
    public function testDismissingThePastSalesNoticeInWooCommerceClearsThePendingMarker(): void
    {
        $listeners = [];
        expectAdded('woocommerce_hide_' . PastSalesNotice::WOOCOMMERCE_NOTICE_NAME . '_notice')->zeroOrMoreTimes()->whenHappen(
            static function (callable $listener) use (&$listeners): void {
                $listeners[] = $listener;
            }
        );

        $setupInfo = new SetupInfoFake([PastSalesNotice::PENDING_KEY => 1700000000]);
        $services = [
            'paypal-pos.order-sync.past-sales-notice' => new PastSalesNotice($setupInfo->container(), true),
            'paypal-pos.order-sync.settings-filter' => new OrderSyncSettingsFilter(),
            'paypal-pos.settings.wc-integration.id' => 'paypal-pos',
        ];
        $container = Mockery::mock(ContainerInterface::class);
        $container->allows('get')->andReturnUsing(static fn (string $id) => $services[$id]);

        (new OrderSyncModule())->run($container);
        foreach ($listeners as $listener) {
            $listener();
        }

        self::assertFalse($setupInfo->has(PastSalesNotice::PENDING_KEY));
    }
}
