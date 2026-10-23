<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Order\OrderScreenPaymentMethod;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class OrderScreenPaymentMethodTest extends BrainMonkeyWpTestCase
{
    private function order(string $createdVia, string $paymentMethod = 'paypal_pos_cash'): object
    {
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_created_via')->andReturn($createdVia);
        $order->shouldReceive('get_payment_method')->andReturn($paymentMethod);

        return $order;
    }

    public function testPosOrdersGetAStandInForTheirPaymentMethod(): void
    {
        self::assertTrue(OrderScreenPaymentMethod::needsStandIn($this->order(PurchaseOrderFactory::CREATED_VIA), ['bacs' => 1]));
    }

    public function testOtherOrdersAndRegisteredGatewaysAreLeftAlone(): void
    {
        self::assertFalse(OrderScreenPaymentMethod::needsStandIn($this->order('checkout'), []));
        self::assertFalse(OrderScreenPaymentMethod::needsStandIn(
            $this->order(PurchaseOrderFactory::CREATED_VIA),
            ['paypal_pos_cash' => 1]
        ));
        self::assertFalse(OrderScreenPaymentMethod::needsStandIn($this->order(PurchaseOrderFactory::CREATED_VIA, ''), []));
    }
}
