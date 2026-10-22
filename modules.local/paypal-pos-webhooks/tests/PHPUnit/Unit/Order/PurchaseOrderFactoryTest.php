<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\UserFactory;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderDefaults;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderMeta;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderRepository;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderSideEffects;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderTaxes;
use Syde\PayPal\PointOfSale\Webhooks\Order\PaymentLabels;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseOrderFactory;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\stubTranslationFunctions;
use function Brain\Monkey\Functions\when;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PurchaseOrderFactoryTest extends BrainMonkeyWpTestCase
{
    private const SOLD_AT = '2026-10-20 16:52:56';

    protected function setUp(): void
    {
        parent::setUp();
        stubTranslationFunctions();
        when('sanitize_key')->returnArg();
        when('wp_json_encode')->alias('json_encode');
        when('wc_stock_amount')->alias('intval');

        $tax = Mockery::mock('alias:WC_Tax');
        $tax->shouldReceive('get_tax_class_slugs')->andReturn(['reduced-rate']);
        $tax->shouldReceive('find_rates')->andReturnUsing(
            static fn (array $args) => $args['tax_class'] === '' ? [1 => ['rate' => '20.0000']] : []
        );
    }

    /**
     * Real payload: one 8.40 coffee with a 20% purchase discount and 20% VAT included.
     */
    private function discountedSale(
        string $quantity = '1',
        int $amount = 672,
        array $payments = [],
        int $vat = 112
    ): Purchase
    {
        return (new PurchaseFactory())->create(
            'short-uuid',
            new DateTime(self::SOLD_AT),
            'GB',
            'GBP',
            (new UserFactory())->create('1', 'Cashier'),
            1,
            7,
            (float) $amount,
            (float) $vat,
            [[
                'quantity' => $quantity,
                'name' => 'Matched Coffee',
                'unitPrice' => 840,
                'variantUuid' => 'variant-uuid',
                'vatPercentage' => 20,
                'rowTaxableAmount' => 560,
                'grossValue' => 840,
                'grossTax' => 140,
            ]],
            $payments ?: [['type' => 'IZETTLE_CASH', 'amount' => $amount]],
            [],
            true,
            false,
            false
        );
    }

    private function factory(OneToManyMapInterface $variants): PurchaseOrderFactory
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        return new PurchaseOrderFactory(
            $variants,
            new OrderDefaults(new PaymentLabels()),
            new OrderTaxes(),
            new OrderSideEffects(),
            Mockery::mock(OrderRepository::class),
            $logger
        );
    }

    private function orderExpectingTotals(
        string $discount,
        string $discountTax,
        string $total = '6.72',
        string $tax = '1.12'
    ): object
    {
        $soldAt = (new DateTime(self::SOLD_AT))->getTimestamp();
        $order = Mockery::mock('overload:WC_Order');
        $order->shouldReceive('set_discount_total')->once()->with($discount);
        $order->shouldReceive('set_discount_tax')->once()->with($discountTax);
        $order->shouldReceive('set_cart_tax')->once()->with($tax);
        $order->shouldReceive('set_total')->once()->with($total);
        $order->shouldReceive('set_status')->once()->with('completed')->ordered();
        $order->shouldReceive('set_date_paid')->once()->with($soldAt)->ordered();
        $order->shouldReceive('set_date_completed')->once()->with($soldAt)->ordered();
        $order->shouldReceive('save')->once()->ordered();
        $order->shouldReceive('get_id')->andReturn(5);
        $order->shouldIgnoreMissing();

        $taxItem = Mockery::mock('overload:WC_Order_Item_Tax');
        $taxItem->shouldReceive('set_tax_total')->once()->with($tax);
        $taxItem->shouldIgnoreMissing();

        return $order;
    }

    public function testRecordsPurchaseDiscountTaxAndSaleDates(): void
    {
        $this->orderExpectingTotals('1.4', '0.28');
        $item = Mockery::mock('overload:WC_Order_Item_Product');
        $item->shouldReceive('set_quantity')->once()->with(1.0);
        $item->shouldReceive('set_subtotal')->once()->with('7');
        $item->shouldReceive('set_total')->once()->with('5.6');
        $item->shouldReceive('set_taxes')->once()->with(['subtotal' => [1 => '1.4'], 'total' => [1 => '1.12']]);
        $item->shouldIgnoreMissing();
        when('wc_get_product')->justReturn(Mockery::mock('WC_Product'));

        $variants = Mockery::mock(OneToManyMapInterface::class);
        $variants->shouldReceive('localId')->with('variant-uuid')->andReturn(10);

        self::assertSame(5, $this->factory($variants)->createOrder($this->discountedSale()));
    }

    public function testUnmatchedLineBecomesFeeWithoutAddingToTheDiscount(): void
    {
        $this->orderExpectingTotals('0', '0');
        $fee = Mockery::mock('overload:WC_Order_Item_Fee');
        $fee->shouldReceive('set_total')->once()->with('5.6');
        $fee->shouldReceive('add_meta_data')->once()->with(OrderMeta::ITEM_VARIANT_UUID, 'variant-uuid');
        $fee->shouldIgnoreMissing();

        $variants = Mockery::mock(OneToManyMapInterface::class);
        $variants->shouldReceive('localId')->andThrow(new IdNotFoundException('variant-uuid'));

        $this->factory($variants)->createOrder($this->discountedSale());
    }

    public function testKeepsQuantitiesBelowOneVisibleAndStoresTheRealOne(): void
    {
        $this->orderExpectingTotals('1.4', '0.28');
        $item = Mockery::mock('overload:WC_Order_Item_Product');
        $item->shouldReceive('set_quantity')->once()->with(1);
        $item->shouldReceive('add_meta_data')->once()->with(OrderMeta::ITEM_QUANTITY, '0.5');
        $item->shouldIgnoreMissing();
        when('wc_get_product')->justReturn(Mockery::mock('WC_Product'));
        $variants = Mockery::mock(OneToManyMapInterface::class);
        $variants->shouldReceive('localId')->andReturn(10);

        $this->factory($variants)->createOrder($this->discountedSale('0.5'));
    }

    public function testAddsTipsAndOtherChargesTheLinesDoNotCover(): void
    {
        $this->orderExpectingTotals('1.4', '0.28', '8.72');
        $item = Mockery::mock('overload:WC_Order_Item_Product');
        $item->shouldIgnoreMissing();
        when('wc_get_product')->justReturn(Mockery::mock('WC_Product'));
        $fees = [];
        $fee = Mockery::mock('overload:WC_Order_Item_Fee');
        $fee->shouldReceive('set_name')->andReturnUsing(static function (string $name) use (&$fees): void {
            $fees[] = $name;
        });
        $fee->shouldReceive('set_total')->andReturnUsing(static function (string $total) use (&$fees): void {
            $fees[] = $total;
        });
        $fee->shouldIgnoreMissing();
        $variants = Mockery::mock(OneToManyMapInterface::class);
        $variants->shouldReceive('localId')->andReturn(10);

        $this->factory($variants)->createOrder(
            $this->discountedSale('1', 872, [['type' => 'IZETTLE_CARD', 'amount' => 872, 'gratuityAmount' => 150]])
        );

        self::assertSame(['Tip', '1.5', 'Other charges', '0.5'], $fees);
    }

    public function testRoundingDifferenceInPurchaseVatIsNotACharge(): void
    {
        $this->orderExpectingTotals('1.4', '0.28', '6.72', '1.11');
        $item = Mockery::mock('overload:WC_Order_Item_Product');
        $item->shouldIgnoreMissing();
        when('wc_get_product')->justReturn(Mockery::mock('WC_Product'));
        $fee = Mockery::mock('overload:WC_Order_Item_Fee');
        $fee->shouldReceive('set_name')->never();
        $variants = Mockery::mock(OneToManyMapInterface::class);
        $variants->shouldReceive('localId')->andReturn(10);

        $this->factory($variants)->createOrder($this->discountedSale('1', 672, [], 111));
    }
}
