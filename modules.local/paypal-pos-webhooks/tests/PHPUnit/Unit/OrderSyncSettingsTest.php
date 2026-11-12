<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\Webhooks\OrderSyncMode;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\when;

class OrderSyncSettingsTest extends BrainMonkeyWpTestCase
{
    /**
     * @param array<string, string> $values
     */
    private function service(string $id, array $values)
    {
        $settings = Mockery::mock(ContainerInterface::class);
        $settings->shouldReceive('has')->andReturnUsing(static fn (string $key) => isset($values[$key]));
        $settings->shouldReceive('get')->andReturnUsing(static fn (string $key) => $values[$key]);
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with('paypal-pos.settings')->andReturn($settings);
        when('wc_get_order_statuses')->justReturn(['wc-completed' => 'Completed', 'wc-processing' => 'Processing']);

        $services = require dirname(__DIR__, 3) . '/services.php';

        return $services[$id]($container);
    }

    /**
     * @dataProvider statuses
     */
    public function testOrderStatusIsValidatedAndStripped(array $values, string $expected): void
    {
        self::assertSame($expected, $this->service('paypal-pos.webhook.order-status', $values));
    }

    public function statuses(): array
    {
        return [
            'not set' => [[], 'completed'],
            'configured' => [['order_sync_status' => 'wc-processing'], 'processing'],
            'no longer registered' => [['order_sync_status' => 'wc-pos-completed'], 'completed'],
        ];
    }

    public function testPaymentTypesAndYesNoSettings(): void
    {
        $values = ['order_sync_payment_types' => ['IZETTLE_CARD'], 'order_sync_auto_recovery' => 'no'];

        self::assertSame(['IZETTLE_CARD'], $this->service('paypal-pos.webhook.order-sync-payment-types', $values));
        self::assertSame([], $this->service('paypal-pos.webhook.order-sync-payment-types', []));
        self::assertSame([], $this->service('paypal-pos.webhook.order-sync-payment-types', ['order_sync_payment_types' => '']));
        $isYes = $this->service('paypal-pos.webhook.order-sync-setting.yes', $values);
        self::assertFalse($isYes('order_sync_auto_recovery'));
        self::assertTrue($isYes('order_sync_import_on_setup'));
    }

    /**
     * @dataProvider modes
     */
    public function testOrderSyncIsEnabledUnlessSwitchedOff(array $values, bool $expected): void
    {
        self::assertSame($expected, $this->service('paypal-pos.webhook.order-sync-enabled', $values));
    }

    public function modes(): array
    {
        return [
            'not set' => [[], true],
            'enabled' => [['order_sync_enabled' => OrderSyncMode::ENABLED], true],
            'disabled' => [['order_sync_enabled' => OrderSyncMode::DISABLED], false],
        ];
    }
}
