<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
use Syde\PayPal\PointOfSale\OrderSync\Order\OrderSideEffects;
use Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;
use Syde\PayPal\PointOfSale\OrderSync\PosOrdersModule;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class PosOrdersModuleTest extends BrainMonkeyWpTestCase
{
    /**
     * @dataProvider originLabels
     */
    public function testFilterOriginLabel(string $sourceType, string $source, string $expected): void
    {
        self::assertSame($expected, PosOrdersModule::filterOriginLabel('Source: %s', $sourceType, $source));
    }

    public function originLabels(): array
    {
        $posType = PurchaseOrderFactory::ORIGIN_SOURCE_TYPE;
        $posSource = PurchaseOrderFactory::ORIGIN_SOURCE;

        return [
            'POS order' => [$posType, $posSource, ''],
            'other source' => [$posType, 'newsletter', 'Source: %s'],
            'other source type' => ['referral', $posSource, 'Source: %s'],
        ];
    }

    public function testKeepsPosOrdersFromReducingStockAgain(): void
    {
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with('paypal-pos.order-sync.order-side-effects')->andReturn(new OrderSideEffects());

        (new PosOrdersModule())->run($container);

        self::assertNotFalse(has_filter('woocommerce_can_reduce_order_stock'));
        self::assertNotFalse(has_filter('woocommerce_prevent_adjust_line_item_product_stock'));
    }

    public function testResetAndUninstallDeleteTheImportOptions(): void
    {
        $options = (new PosOrdersModule())->extensions()['paypal-pos.onboarding.resettable.options'](['other']);

        self::assertSame(['other', PurchaseBackfill::STATUS_OPTION, PurchaseBackfill::WEBHOOK_GAP_OPTION], $options);
    }
}
