<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\UserFactory;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderDefaults;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderMeta;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderRepository;
use Syde\PayPal\PointOfSale\Webhooks\Order\OrderTaxes;
use Syde\PayPal\PointOfSale\Webhooks\Order\PaymentLabels;
use Syde\PayPal\PointOfSale\Webhooks\Order\PurchaseRefundFactory;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Actions\expectAdded;
use function Brain\Monkey\Filters\expectAdded as expectFilterAdded;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\stubEscapeFunctions;
use function Brain\Monkey\Functions\stubTranslationFunctions;
use function Brain\Monkey\Functions\when;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PurchaseRefundFactoryTest extends BrainMonkeyWpTestCase
{
    private const REFUNDED_AT = '2026-10-20 17:48:43';
    private const REFUND_ID = 99;

    /**
     * @var array<string, callable> ConfirmedSave's listeners by WC object type.
     */
    private array $recordSave = [];

    protected function setUp(): void
    {
        parent::setUp();
        stubTranslationFunctions();
        stubEscapeFunctions();
        when('sanitize_key')->returnArg();
        when('wp_json_encode')->alias('json_encode');
        when('wc_format_decimal')->returnArg();
        foreach (['order', 'order_refund'] as $type) {
            expectAdded("woocommerce_after_{$type}_object_save")->zeroOrMoreTimes()->whenHappen(
                function (callable $recordSave) use ($type): void {
                    $this->recordSave[$type] = $recordSave;
                }
            );
        }
    }

    /**
     * Lets ConfirmedSave see that the save got through to its last hook.
     */
    private function saved(string $type, int $id): void
    {
        $record = Mockery::mock('WC_Data');
        $record->shouldReceive('get_id')->andReturn($id);
        ($this->recordSave[$type])($record);
    }

    /**
     * Real payload: one 8.40 coffee refunded in cash, 1.40 of it VAT.
     */
    private function refund(int $amount = -840, array $payments = [['type' => 'IZETTLE_CASH', 'amount' => -840]]): Purchase
    {
        $refund = (new PurchaseFactory())->create(
            'refund-uuid',
            new DateTime(self::REFUNDED_AT),
            'GB',
            'GBP',
            (new UserFactory())->create('1', 'Cashier'),
            1,
            1,
            (float) $amount,
            -140.0,
            [[
                'quantity' => '-1',
                'name' => 'Coffee',
                'variantUuid' => 'variant-uuid',
                'vatPercentage' => 20,
                'rowTaxableAmount' => -700,
                'grossValue' => -840,
                'grossTax' => -140,
            ]],
            $payments,
            [],
            false,
            true,
            false
        );

        return $refund->setRefundsPurchaseUuid('original-uuid');
    }

    private function factory(OrderRepository $orders, ?LoggerInterface $logger = null): PurchaseRefundFactory
    {
        if ($logger === null) {
            $logger = Mockery::mock(LoggerInterface::class);
            $logger->shouldIgnoreMissing();
        }

        return new PurchaseRefundFactory(
            $orders,
            new OrderDefaults(new PaymentLabels()),
            new OrderTaxes(),
            new PaymentLabels(),
            $logger
        );
    }

    /**
     * @param int|null $storedOrphanId What the lookup finds once an orphan refund order was saved.
     */
    private function orders(?int $orphanId, ?int $orderId, ?int $storedOrphanId = null): OrderRepository
    {
        $orders = Mockery::mock(OrderRepository::class);
        $orders->shouldReceive('findIdByRefundPurchaseUuid')
            ->with('refund-uuid')
            ->andReturn($orphanId, $storedOrphanId ?? $orphanId);
        $orders->shouldReceive('findIdByPurchaseUuid')->with('original-uuid')->andReturn($orderId);
        $orders->shouldReceive('findRefundIdByRefundPurchaseUuid')->with('refund-uuid')->andReturn(self::REFUND_ID);

        return $orders;
    }

    private function order(
        string $remaining,
        array $refunds = [],
        array $unapplied = [],
        ?object $storedRefund = null
    ): object {

        $order = Mockery::mock('WC_Order');
        $order->shouldReceive('get_id')->andReturn(7);
        $order->shouldReceive('get_meta')->with(OrderMeta::UNAPPLIED_REFUND_UUID, false)->andReturn($unapplied);
        $order->shouldReceive('get_refunds')->andReturn($refunds);
        $order->shouldReceive('get_remaining_refund_amount')->andReturn($remaining);
        $order->shouldReceive('get_status')->andReturn('completed')->byDefault();
        $order->shouldReceive('get_items')->andReturn([]);
        $order->shouldReceive('get_item')->andReturnNull();
        when('wc_get_order')->alias(static fn (int $id) => $id === self::REFUND_ID ? $storedRefund : $order);

        return $order;
    }

    public function testSkipsRefundAlreadyRecordedAsOrphan(): void
    {
        expect('wc_get_order')->never();
        expect('wc_create_refund')->never();

        $this->factory($this->orders(99, 7))->createRefund($this->refund());
    }

    public function testSkipsRefundAlreadyRecordedOnTheOrder(): void
    {
        $existing = Mockery::mock('WC_Order_Refund');
        $existing->shouldReceive('get_meta')->with(OrderMeta::REFUND_PURCHASE_UUID)->andReturn('refund-uuid');
        $existing->shouldReceive('get_reason')->andReturn('');
        $this->order('8.40', [$existing]);
        expect('wc_create_refund')->never();

        $this->factory($this->orders(null, 7))->createRefund($this->refund());
    }

    public function testRecognisesAnUntaggedRefundByItsReason(): void
    {
        // A crash between the refund's first save and tagging leaves only the reason.
        $existing = Mockery::mock('WC_Order_Refund');
        $existing->shouldReceive('get_meta')->andReturn('');
        $existing->shouldReceive('get_reason')->andReturn('Refund refund-uuid via PayPal POS Cash of purchase original-uuid');
        $this->order('8.40', [$existing]);
        expect('wc_create_refund')->never();

        $this->factory($this->orders(null, 7))->createRefund($this->refund());
    }

    public function testNotesRefundLargerThanWhatIsLeftInsteadOfFailing(): void
    {
        $order = $this->order('0.00');
        $order->shouldReceive('add_meta_data')->once()->with(OrderMeta::UNAPPLIED_REFUND_UUID, 'refund-uuid');
        $order->shouldReceive('save')->once();
        $order->shouldReceive('add_order_note')->once();
        expect('wc_create_refund')->never();
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')->once();

        $this->factory($this->orders(null, 7), $logger)->createRefund($this->refund());
    }

    public function testNotesAnUnappliableRefundOnlyOnce(): void
    {
        $order = $this->order('0.00', [], [(object) ['value' => 'refund-uuid']]);
        $order->shouldNotReceive('add_order_note');
        expect('wc_create_refund')->never();

        $this->factory($this->orders(null, 7))->createRefund($this->refund());
    }

    public function testTagsTheRefundWithItsZettleUuid(): void
    {
        $record = Mockery::mock('WC_Order_Refund');
        $record->shouldReceive('get_id')->andReturn(self::REFUND_ID);
        $this->order('8.40', [], [], $record);
        $tag = null;
        expectAdded('woocommerce_create_refund')->once()->whenHappen(
            static function (callable $callback) use (&$tag): void {
                $tag = $callback;
            }
        );

        $record->shouldReceive('update_meta_data')->once()->with(OrderMeta::REFUND_PURCHASE_UUID, 'refund-uuid');
        $record->shouldReceive('update_meta_data')->once()->with(OrderMeta::REFUND_PAYMENTS, Mockery::type('string'));
        $record->shouldNotReceive('save');
        expect('wc_create_refund')->once()->andReturnUsing(
            function (array $args) use (&$tag, $record) {
                self::assertSame('8.4', $args['amount']);
                self::assertSame((new DateTime(self::REFUNDED_AT))->getTimestamp(), $args['date_created']);
                self::assertStringContainsString('refund-uuid', $args['reason']);
                $tag($record, $args);
                $this->saved('order_refund', self::REFUND_ID);

                return $record;
            }
        );

        $this->factory($this->orders(null, 7))->createRefund($this->refund());
    }

    /**
     * @dataProvider refundedAmountProvider
     */
    public function testRefundedAmountAloneDecidesWhetherTheOrderIsPartiallyRefunded(
        string $remaining,
        bool $expectedPartial
    ): void {

        $record = Mockery::mock('WC_Order_Refund');
        $record->shouldReceive('get_id')->andReturn(self::REFUND_ID);
        $record->shouldIgnoreMissing();
        $this->order($remaining, [], [], $record);
        $partially = null;
        expectFilterAdded('woocommerce_order_is_partially_refunded')->once()->whenHappen(
            static function (callable $callback) use (&$partially): void {
                $partially = $callback;
            }
        );
        $results = [];
        expect('wc_create_refund')->once()->andReturnUsing(
            function () use (&$partially, &$results, $record) {
                // WC says partial because of a free line left out; another order keeps WC's answer.
                $results = [$partially(true, 7, 99), $partially(true, 8, 99)];
                $this->saved('order_refund', self::REFUND_ID);

                return $record;
            }
        );

        $this->factory($this->orders(null, 7))->createRefund($this->refund());

        self::assertSame([$expectedPartial, true], $results);
    }

    public function testDeletesARefundWhoseSaveDidNotFinishAndRestoresTheOrderStatus(): void
    {
        $record = Mockery::mock('WC_Order_Refund');
        $record->shouldReceive('get_id')->andReturn(self::REFUND_ID);
        $record->shouldReceive('delete')->once()->with(true);
        $record->shouldIgnoreMissing();
        $order = $this->order('8.40', [], [], $record);
        // WC marks the order refunded inside wc_create_refund(), before the refund is confirmed.
        $order->shouldReceive('get_status')->andReturn('completed', 'refunded');
        $order->shouldReceive('set_status')->once()->with('completed');
        $order->shouldReceive('save')->once();
        expect('wc_create_refund')->once()->andReturn($record);

        $this->expectException(RuntimeException::class);

        $this->factory($this->orders(null, 7))->createRefund($this->refund());
    }

    public function refundedAmountProvider(): array
    {
        return [
            'whole remaining amount' => ['8.40', false],
            'part of the remaining amount' => ['20.00', true],
        ];
    }

    public function testLinksOrphanRefundsToTheSaleOnceItSyncs(): void
    {
        $sale = Mockery::mock('WC_Order');
        $sale->shouldReceive('add_order_note')->once()->with(Mockery::pattern('/orphan refund order #99/'));
        $orphan = Mockery::mock('WC_Order');
        $orphan->shouldReceive('add_order_note')->once()->with(Mockery::pattern('/synced as order #7/'));
        when('wc_get_order')->alias(static fn (int $id) => $id === 7 ? $sale : $orphan);

        $orders = Mockery::mock(OrderRepository::class);
        $orders->shouldReceive('findOrphanIdsForPurchaseUuid')->with('original-uuid')->andReturn([99]);

        $this->factory($orders)->linkOrphanRefunds('original-uuid', 7);
    }

    public function testOrphanRefundRecordsNegativeNetAndVatAtTheRefundTime(): void
    {
        $taxes = Mockery::mock('alias:WC_Tax');
        $taxes->shouldReceive('get_tax_class_slugs')->andReturn([]);
        $taxes->shouldReceive('find_rates')->andReturn([1 => ['rate' => '20.0000']]);

        $refundedAt = (new DateTime(self::REFUNDED_AT))->getTimestamp();
        $order = Mockery::mock('overload:WC_Order');
        $order->shouldReceive('update_meta_data')->with(OrderMeta::ORPHAN_REFUND, 'yes')->once();
        $order->shouldReceive('update_meta_data')->with(OrderMeta::ORPHAN_REFUNDS_PURCHASE_UUID, 'original-uuid')->once();
        $order->shouldReceive('set_cart_tax')->once()->with('-1.4');
        $order->shouldReceive('set_total')->once()->with('-8.4');
        $order->shouldReceive('set_date_paid')->once()->with($refundedAt);
        $order->shouldReceive('get_id')->andReturn(9);
        $order->shouldReceive('save')->once()->andReturnUsing(fn () => $this->saved('order', 9));
        $order->shouldIgnoreMissing();
        $fee = Mockery::mock('overload:WC_Order_Item_Fee');
        $fee->shouldReceive('set_total')->once()->with('-7');
        $fee->shouldReceive('set_taxes')->once()->with(['total' => [1 => '-1.4']]);
        $fee->shouldIgnoreMissing();
        $tax = Mockery::mock('overload:WC_Order_Item_Tax');
        $tax->shouldReceive('set_tax_total')->once()->with('-1.4');
        $tax->shouldIgnoreMissing();

        $this->factory($this->orders(null, null, 9))->createRefund($this->refund());
    }

    public function testDeletesAPartlyStoredOrphanRefundOrderAndThrows(): void
    {
        $taxes = Mockery::mock('alias:WC_Tax');
        $taxes->shouldReceive('get_tax_class_slugs')->andReturn([]);
        $taxes->shouldReceive('find_rates')->andReturn([1 => ['rate' => '20.0000']]);
        $order = Mockery::mock('overload:WC_Order');
        $order->shouldReceive('get_id')->andReturn(9);
        $order->shouldReceive('delete')->once()->with(true);
        $order->shouldReceive('add_order_note')->never();
        $order->shouldIgnoreMissing();
        Mockery::mock('overload:WC_Order_Item_Fee')->shouldIgnoreMissing();
        Mockery::mock('overload:WC_Order_Item_Tax')->shouldIgnoreMissing();

        $this->expectException(RuntimeException::class);

        $this->factory($this->orders(null, null))->createRefund($this->refund());
    }

    public function testOrphanRefundOfATipGetsANegativeTipLine(): void
    {
        $taxes = Mockery::mock('alias:WC_Tax');
        $taxes->shouldReceive('get_tax_class_slugs')->andReturn([]);
        $taxes->shouldReceive('find_rates')->andReturn([1 => ['rate' => '20.0000']]);
        $order = Mockery::mock('overload:WC_Order');
        $order->shouldReceive('get_id')->andReturn(9);
        $order->shouldReceive('save')->andReturnUsing(fn () => $this->saved('order', 9));
        $order->shouldIgnoreMissing();
        Mockery::mock('overload:WC_Order_Item_Tax')->shouldIgnoreMissing();
        $fees = [];
        $fee = Mockery::mock('overload:WC_Order_Item_Fee');
        $fee->shouldReceive('set_name')->andReturnUsing(static function (string $name) use (&$fees): void {
            $fees[] = $name;
        });
        $fee->shouldReceive('set_total')->andReturnUsing(static function (string $total) use (&$fees): void {
            $fees[] = $total;
        });
        $fee->shouldIgnoreMissing();

        $this->factory($this->orders(null, null, 9))->createRefund(
            $this->refund(-1040, [['type' => 'IZETTLE_CARD', 'amount' => -1040, 'gratuityAmount' => -200]])
        );

        self::assertSame(['Refund: Coffee × 1', '-7', 'Tip', '-2'], $fees);
    }
}
