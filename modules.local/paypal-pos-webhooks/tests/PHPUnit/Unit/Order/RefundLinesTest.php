<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Order\OrderMeta;
use Syde\PayPal\PointOfSale\Webhooks\Order\RefundLines;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\stubTranslationFunctions;

class RefundLinesTest extends BrainMonkeyWpTestCase
{
    private const VARIANT = 'variant-uuid';

    /**
     * Coffees refunded at 8.40 each: 7.00 net, 1.40 VAT.
     */
    private function refundLine(string $variant = self::VARIANT, string $name = 'Coffee', string $quantity = '1'): array
    {
        $factor = (float) $quantity;

        return [
            'quantity' => "-{$quantity}",
            'name' => $name,
            'variantUuid' => $variant,
            'rowTaxableAmount' => (int) round(-700 * $factor),
            'grossValue' => (int) round(-840 * $factor),
            'grossTax' => (int) round(-140 * $factor),
        ];
    }

    private function item(string $class, string $variant, string $name, string $total, array $taxes): object
    {
        $item = Mockery::mock($class);
        $item->shouldReceive('get_meta')->with(OrderMeta::ITEM_VARIANT_UUID)->andReturn($variant);
        $item->shouldReceive('get_name')->andReturn($name);
        $item->shouldReceive('get_total')->andReturn($total);
        $item->shouldReceive('get_taxes')->andReturn(['total' => $taxes]);

        return $item;
    }

    private function product(string $total): object
    {
        return $this->item('WC_Order_Item_Product', self::VARIANT, 'Coffee', $total, [1 => '1.40']);
    }

    private function fee(string $variant, string $name, string $total, array $taxes = [1 => '1.40']): object
    {
        return $this->item('WC_Order_Item_Fee', $variant, $name, $total, $taxes);
    }

    /**
     * @param array<int, object> $products
     * @param array<int, object> $fees
     * @param array<int, float> $refunded Already refunded net per item.
     */
    private function order(array $products, array $fees = [], array $refunded = []): object
    {
        $items = $products + $fees;
        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_items')->with('line_item')->andReturn($products);
        $order->shouldReceive('get_items')->with('fee')->andReturn($fees);
        $order->shouldReceive('get_item')->andReturnUsing(static fn (int $id) => $items[$id]);
        $order->shouldReceive('get_total_refunded_for_item')
            ->andReturnUsing(static fn (int $id) => $refunded[$id] ?? 0.0);

        return $order;
    }

    public function testSpreadsOverRepeatedVariantLines(): void
    {
        $order = $this->order([11 => $this->product('7.00'), 12 => $this->product('14.00')]);

        self::assertSame(
            [
                11 => ['qty' => 1.0, 'refund_total' => '7', 'refund_tax' => [1 => '1.4']],
                12 => ['qty' => 1.0, 'refund_total' => '7', 'refund_tax' => [1 => '1.4']],
            ],
            RefundLines::forRefund($order, [$this->refundLine(self::VARIANT, 'Coffee', '2')], 'GBP')
        );
    }

    public function testSkipsWhatWasAlreadyRefunded(): void
    {
        $order = $this->order([11 => $this->product('7.00'), 12 => $this->product('7.00')], [], [11 => 7.0]);

        self::assertSame(
            [12 => ['qty' => 1.0, 'refund_total' => '7', 'refund_tax' => [1 => '1.4']]],
            RefundLines::forRefund($order, [$this->refundLine()], 'GBP')
        );
    }

    public function testRefundsFractionalQuantitiesInFull(): void
    {
        $order = $this->order([11 => $this->product('3.50')]);

        self::assertSame(
            [11 => ['qty' => 0.5, 'refund_total' => '3.5', 'refund_tax' => [1 => '0.7']]],
            RefundLines::forRefund($order, [$this->refundLine(self::VARIANT, 'Coffee', '0.5')], 'GBP')
        );
    }

    public function testOverflowsFromProductToFeeLineOfTheSameVariant(): void
    {
        $order = $this->order([11 => $this->product('7.00')], [21 => $this->fee(self::VARIANT, 'Coffee', '7.00')]);

        self::assertSame(
            [
                11 => ['qty' => 1.0, 'refund_total' => '7', 'refund_tax' => [1 => '1.4']],
                21 => ['qty' => 0.0, 'refund_total' => '7', 'refund_tax' => [1 => '1.4']],
            ],
            RefundLines::forRefund($order, [$this->refundLine(self::VARIANT, 'Coffee', '2')], 'GBP')
        );
    }

    public function testMatchesUnnamedCustomAmountsByTheDefaultName(): void
    {
        stubTranslationFunctions();
        $order = $this->order([], [21 => $this->fee('', 'PayPal POS item', '7.00', [])]);

        self::assertSame(
            [21 => ['qty' => 0.0, 'refund_total' => '7', 'refund_tax' => [0 => '1.4']]],
            RefundLines::forRefund($order, [$this->refundLine('', '')], 'GBP')
        );
    }

    public function testNothingIsLeftOnAFullyRefundedFee(): void
    {
        $order = $this->order([], [21 => $this->fee('', 'Custom cake', '7.00')], [21 => 7.0]);

        self::assertSame([], RefundLines::forRefund($order, [$this->refundLine('', 'Custom cake')], 'GBP'));
    }
}
