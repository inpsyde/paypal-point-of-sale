<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Order\LineAmounts;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class LineAmountsTest extends BrainMonkeyWpTestCase
{
    public function testUsesZettleRowFiguresForVatInclusiveLine(): void
    {
        $amounts = LineAmounts::fromLine([
            'quantity' => '2',
            'unitPrice' => 840,
            'vatPercentage' => 20,
            'rowTaxableAmount' => 1400,
            'grossValue' => 1680,
            'grossTax' => 280,
        ]);

        self::assertSame(2.0, $amounts->quantity());
        self::assertSame(1400, $amounts->netMinor());
        self::assertSame(280, $amounts->taxMinor());
        self::assertSame(1400, $amounts->subtotalNetMinor());
        self::assertSame(280, $amounts->subtotalTaxMinor());
        self::assertSame(20.0, $amounts->vatPercentage());
    }

    public function testPurchaseDiscountReducesNetAndTaxButNotSubtotal(): void
    {
        // Real payload: 8.40 coffee, 20% purchase discount, 20% VAT included -> 6.72 paid.
        $amounts = LineAmounts::fromLine([
            'quantity' => '1',
            'unitPrice' => 840,
            'vatPercentage' => 20,
            'rowTaxableAmount' => 560,
            'grossValue' => 840,
            'grossTax' => 140,
        ]);

        self::assertSame(560, $amounts->netMinor());
        self::assertSame(112, $amounts->taxMinor());
        self::assertSame(700, $amounts->subtotalNetMinor());
        self::assertSame(140, $amounts->subtotalTaxMinor());
    }

    public function testFallsBackToUnitPriceMinusDiscountWithoutRowFigures(): void
    {
        $amounts = LineAmounts::fromLine([
            'quantity' => '1',
            'unitPrice' => 70000,
            'discountValue' => 1000,
        ]);

        self::assertSame(69000, $amounts->netMinor());
        self::assertSame(0, $amounts->taxMinor());
        self::assertSame(70000, $amounts->subtotalNetMinor());
    }

    public function testRefundLineAmountsAreAbsolute(): void
    {
        $amounts = LineAmounts::fromLine([
            'quantity' => '-1',
            'unitPrice' => 840,
            'rowTaxableAmount' => -700,
            'grossValue' => -840,
            'grossTax' => -140,
        ]);

        self::assertSame(1.0, $amounts->quantity());
        self::assertSame(700, $amounts->netMinor());
        self::assertSame(140, $amounts->taxMinor());
    }

    public function testKeepsFractionalQuantities(): void
    {
        $amounts = LineAmounts::fromLine(['quantity' => '0.5', 'unitPrice' => 1000]);

        self::assertSame(0.5, $amounts->quantity());
        self::assertSame(500, $amounts->netMinor());
    }
}
