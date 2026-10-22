<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Order\OrderSideEffects;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class OrderSideEffectsTest extends BrainMonkeyWpTestCase
{
    public function testSuppressesEmailsOnlyForPosOrders(): void
    {
        $pos = Mockery::mock('WC_Order');
        $pos->shouldReceive('get_created_via')->andReturn(PurchaseOrderFactory::CREATED_VIA);
        $web = Mockery::mock('WC_Order');
        $web->shouldReceive('get_created_via')->andReturn('checkout');
        $sideEffects = new OrderSideEffects();

        self::assertFalse($sideEffects->emailEnabled(true, $pos));
        self::assertTrue($sideEffects->emailEnabled(true, $web));
        self::assertFalse($sideEffects->emailEnabled(false, $web));
    }

    public function testBlocksStockReductionOnlyWhileCreating(): void
    {
        $sideEffects = new OrderSideEffects();

        $during = $sideEffects->withoutStockReduction(
            static fn () => has_filter('woocommerce_can_reduce_order_stock', [$sideEffects, 'disable'])
        );

        self::assertNotFalse($during);
        self::assertFalse(has_filter('woocommerce_can_reduce_order_stock', [$sideEffects, 'disable']));
    }
}
