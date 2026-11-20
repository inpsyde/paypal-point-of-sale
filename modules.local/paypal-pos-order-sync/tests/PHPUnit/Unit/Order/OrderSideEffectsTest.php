<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\OrderSync\Order\OrderSideEffects;
use Syde\PayPal\PointOfSale\OrderSync\Order\PurchaseOrderFactory;
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

    public function testBlocksStockChangesOnlyForPosOrders(): void
    {
        $pos = Mockery::mock('WC_Order');
        $pos->shouldReceive('get_created_via')->andReturn(PurchaseOrderFactory::CREATED_VIA);
        $web = Mockery::mock('WC_Order');
        $web->shouldReceive('get_created_via')->andReturn('checkout');
        $posLine = Mockery::mock('WC_Order_Item');
        $posLine->shouldReceive('get_order')->andReturn($pos);
        $webLine = Mockery::mock('WC_Order_Item');
        $webLine->shouldReceive('get_order')->andReturn($web);
        $sideEffects = new OrderSideEffects();

        self::assertFalse($sideEffects->canReduceOrderStock(true, $pos));
        self::assertTrue($sideEffects->canReduceOrderStock(true, $web));
        self::assertFalse($sideEffects->canReduceOrderStock(false, $web));
        self::assertTrue($sideEffects->preventLineItemStockAdjustment(false, $posLine));
        self::assertFalse($sideEffects->preventLineItemStockAdjustment(false, $webLine));
        self::assertTrue($sideEffects->preventLineItemStockAdjustment(true, $webLine));
    }
}
